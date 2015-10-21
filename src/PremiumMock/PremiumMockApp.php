<?php

namespace PremiumMock;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use GuzzleHttp\Client as HttpClient;

class PremiumMockApp implements MessageComponentInterface {

    protected $clients;
    protected $msgCounter = 0;
    
    /**
     *
     * @var HttpClient 
     */
    protected $httpClient;
    
    protected $config;

    public function __construct(array $config) {
        $this->clients = new \SplObjectStorage;
        $this->httpClient = new HttpClient();
        $this->config = $config;
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";
    }
    
    public function broadcast($messageType, array $messageData, $from = null)
    {
        $message = json_encode(array(
            'type'  => $messageType,
            'data'  => $messageData
        ));
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                // The sender is not the receiver, send to each client connected
                $client->send($message);
            }
        }        
    }
    
    public function sendError($message, $details = null)
    {
        $this->broadcast('error', array('message' => $message, 'details' => $details));
    }
    
    public function processMt(array $parameters)
    {
        $this->broadcast('mt', $parameters);
    }
    
    protected function parseXMLResponse($xmlstring)
    {
        $xml = simplexml_load_string($xmlstring);
        $json = json_encode($xml);
        return json_decode($json,TRUE);        
    }
    
    public function postMo(array $messageArr)
    {
        try {
            $this->msgCounter++;
            $words = explode(' ', $messageArr['text']);
            $moParams = array_merge($this->config['mo'], $messageArr, array(
                'message_id' => $this->msgCounter,
                'keyword'   => $words[0] . '@' . $messageArr['short_id']
            ));
            echo "Posting params from MO to client @" . $messageArr['url'] . ': ' . json_encode($moParams) . "\n";
            $response = $this->httpClient->post($messageArr['url'], [
                'body' => $moParams
            ]);
            if($response->getStatusCode() != 200){
                echo 'received MO reply with status code: ' .$response->getStatusCode() . ', and body' . $response->getBody() . "\n";
                return $this->sendError($response->getBody());
            }
            $responseBody = $response->getBody();
            echo 'received MO reply:' . $responseBody . "\n";
            $this->broadcast('mo_reply', array('message' => $this->parseXMLResponse($responseBody)));
        } catch(\GuzzleHttp\Exception\RequestException $requestException){
            echo 'received MO reply error of class [' . get_class($requestException) . '] and message: '  . $requestException->getMessage() .  "\n";
            if($requestException->hasResponse()){
                echo "\nbody: " . $requestException->getResponse()->getBody() . "\n";
                echo "\ncode: " . $requestException->getResponse()->getStatusCode() . "\n";
                
                $this->sendError($requestException->getMessage(), $this->parseXMLResponse($requestException->getResponse()->getBody()));
            }
            $this->sendError($requestException->getMessage());            
            
        } catch (\Exception $exc) {
            echo 'received MO reply error of class [' . get_class($exc) . '] and message: '  . $exc->getMessage() .  "\n";            
            $this->sendError($exc->getMessage());
        }      
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $message = json_decode($msg, true);
        switch ($message['type']) {
            case 'mo':
                $this->postMo($message['data']);
                break;

            default:
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

}
