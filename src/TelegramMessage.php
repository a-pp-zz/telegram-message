<?php
namespace AppZz\Http;
use AppZz\Http\CurlClient;
use AppZz\Helpers\Arr;

/**
 * Telegram message library
 */
class TelegramMessage {

    const ENDPOINT = 'https://api.telegram.org/bot%s/sendMessage';
    const UA       = 'AppZz Telegram Client';

    private $_params;
    private $_token;

    /**
     * Allowed methods
     * @var array
     */
    private $_methods = [
        'token',
        'chat_id',
        'text',
        'html',
        'markdown',
        'silent'
    ];

	public function __construct ($token = NULL, $chat_id = NULL)
    {
        if ( ! empty ($chat_id)) {
            $this->_params['chat_id'] = $chat_id;
        }

        if ( ! empty ($token)) {
            $this->_token = $token;
        }
	}

    public static function factory ($token = NULL, $chat_id = NULL)
    {
        return new TelegramMessage ($token, $chat_id);
    }

    /**
     * Set params avoid setters
     * @param  array  $params
     * @return $this
     */
    public function params (array $params = [])
    {
        foreach ($this->_params as $key=>$value) {
            $this->$key($value);
        }

        return $this;
    }

    public function __call ($method, $params)
    {
        if ( ! in_array ($method, $this->_methods)) {
            throw new \BadMethodCallException ('Wrong method: ' . $method);
        }

        $value = Arr::get($params, 0);

        switch ($method) {
            case 'silent':
                $method = 'disable_notification';
                $value = (bool)$value;
            break;

            case 'html':
                $method = 'parse_mode';
                $value = 'HTML';
            break;

            case 'markdown':
                $method = 'parse_mode';
                $value = 'MarkdownV2';
            break;

            case 'token':
                $this->_token = $value;
                $method = null;
            break;
        }

        if ( ! empty ($method)) {
            $this->_params[$method] = $value;
        }

        return $this;
    }

    public function send ()
    {
        if (empty($this->_params['chat_id'])) {
            throw new \InvalidArgumentException ('Chat Id not specified');
        }

        if (empty($this->_token)) {
            throw new \InvalidArgumentException ('Token not specified');
        }

        $endpoint = sprintf (TelegramMessage::ENDPOINT, $this->_token);

        $request = CurlClient::post($endpoint, $this->_params)
                        ->json()
                        ->user_agent(TelegramMessage::UA)
                        ->accept('gzip', 'json');

        $response = $request->send();
        $ret = new \stdClass;
        $ret->response = $response->get_status();
        $ret->result = $response->get_body();
        return $ret;
    }
}
