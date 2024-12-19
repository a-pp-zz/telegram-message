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
    private $_title;
    private $_text;

    /**
     * Allowed methods
     * @var array
     */
    private $_methods = [
        'token',
        'chat_id',
        'text',
        'markdown', //depracated, always on
        'silent',
        'title',
        'raw_text',
        'url'
    ];

	public function __construct ($token = NULL, $chat_id = NULL)
    {
        if ( ! empty ($chat_id)) {
            $this->_params['chat_id'] = $chat_id;
        }

        if ( ! empty ($token)) {
            $this->_token = $token;
        }

        $this->_text = [];
        $this->_title = '';
        $this->_params['parse_mode'] = 'MarkdownV2';
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

            case 'markdown':
                $method = null;
            break;

            case 'token':
                $this->_token = $value;
                $method = null;
            break;

            case 'title':
                $method = null;
                $this->_title = $value;
            break;

            case 'text':
                $this->_text[] = $this->escape_text ($value);
            break;

            case 'url':
                $value2 = Arr::get($params, 1);
                $this->_text[] = sprintf ('[%s](%s)', $this->escape_text ($value2), $this->escape_text ($value));
            break;

            case 'raw_text' :
                $method = 'text';
            break;
        }

        if ( ! empty ($method)) {
            $this->_params[$method] = $value;
        }

        return $this;
    }

    public function escape_text ($text = '')
    {
        return str_replace (["\n", "_", "*", "[", "]", "(", ")", "~", "`", ">", "#", "+", "-", "=", "|", "{", "}", ".", "!"], [chr(10), "\_", "\*", "\[", "\]", "\(", "\)", "\~", "\`", "\>", "\#", "\+", "\-", "\=", "\|", "\{", "\}", "\.", "\!"], $text);
    }

    public function send ()
    {
        if (empty($this->_params['chat_id'])) {
            throw new \InvalidArgumentException ('Chat Id not specified');
        }

        if (empty($this->_token)) {
            throw new \InvalidArgumentException ('Token not specified');
        }

        $message = '';

        if ( ! empty ($this->_title)) {
            $message .= '*' . $this->escape_text ($this->_title) . "*\n";
        }

        foreach ($this->_text as $num=>$txt) {
            $message .= $txt;

            if ($num !== (count($this->_text)-1)) {
                $message .= $this->escape_text ("\n");
            }
        }

        $this->_params['text'] = $message;

        $request = CurlClient::post(sprintf (TelegramMessage::ENDPOINT, $this->_token), $this->_params)
                        ->json()
                        ->user_agent(TelegramMessage::UA)
                        ->accept('gzip', 'json');

        $response = $request->send();
        $ret = new \stdClass;
        $ret->response = $response->get_status();
        $ret->result = $response->get_body();

        unset ($this->_params);
        return $ret;
    }
}
