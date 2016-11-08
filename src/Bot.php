<?php
namespace Slack;

use GuzzleHttp\Client as Guzzle;

/**
 * @author chey
 */
abstract class Bot
{
    /**
     * @var string
     */
    const SLACK_BASE_URI = 'https://slack.com/api/';

    /**
     * @var string
     */
    protected $slackToken;

    /**
     * @var GuzzleHttp\Client;
     */
    protected $slackClient;

    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;

    /**
     * Contains the data returned from Slack after rtm.start
     * @see getSlackWSUrl()
     *
     * @var array
     */
    protected $rtmdata;

    /**
     * @var \Ratchet\Client\WebSocket
     */
    protected $webSocket;

    /**
     * @var int
     */
    protected $msgID = 0;

    /**
     * Constructor
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($token)
    {
        $this->slackToken = $token;

        $this->slackClient = new Guzzle([
            'base_uri' => self::SLACK_BASE_URI
        ]);
    }

    public function run()
    {
        $this->loop = \React\EventLoop\Factory::create();
        $connector = new \Ratchet\Client\Connector($this->loop);

        $connector($this->getSlackWSUrl())->then(function(\Ratchet\Client\WebSocket $ws) {
            $this->webSocket = $ws;

            $ws->on('message', function(\Ratchet\RFC6455\Messaging\MessageInterface $msg) {
                $this->handleMessage($msg);
            });

            $ws->on('close', function($code = null, $reason = null) {
                echo "Connection closed ({$code} - {$reason})\n";
            });
        }, function ($e) {
            echo "Could not connect: {$e->getMessage()}\n";
            $this->loop->stop();
        });

        $this->loop->run();
    }

    /**
     * @return string url
     */
    public function getSlackWSUrl()
    {
        $response = $this->slackClient->request('POST', 'rtm.start', [
            'form_params' => [
                'token' => $this->slackToken,
                'simple_latest' => true,
                'no_unreads' => true
            ]
        ]);

        $this->rtmdata = json_decode($response->getBody());

        if (!$this->rtmdata->ok) {
            die(sprintf('ERROR: Trouble communicating with Slack (%s)', $this->rtmdata->error));
        }

        return $this->rtmdata->url;
    }

    /**
     * Send message over the web socket.
     *
     * @param string $text
     * @param string $channel
     */
    public function send($text, $channel)
    {
        $this->webSocket->send(json_encode([
            'id' => ++$this->msgID,
            'type' => 'message',
            'channel' => $channel,
            'text' => $text,
            'mrkdwn' => true
        ]));
    }

    /**
     * Let channel know the bot is typing.
     *
     * @param string $channel
     */
    public function typing($channel)
    {
        $this->webSocket->send(json_encode([
            'id' => ++$this->msgID,
            'type' => 'typing',
            'channel' => $channel
        ]));
    }

    /**
     * @param \Ratchet\RFC6455\Messaging\MessageInterface $msg
     */
    abstract public function handleMessage(\Ratchet\RFC6455\Messaging\MessageInterface $msg);
}
