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
        'markdown',
        'silent',
        'title',
        'raw_text'
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

            case 'markdown':
                $method = 'parse_mode';
                $value = 'MarkdownV2';
            break;

            case 'token':
                $this->_token = $value;
                $method = null;
            break;

            case 'text':
            case 'title':
                $parse_mode = Arr::get ($this->_params, 'parse_mode');
                $asterix = '';

                switch ($parse_mode) :
                    case 'MarkdownV2':
                        $asterix = '*';
                        $value = $this->escape_text ($value);
                    break;
                endswitch;

            if ($method == 'title') :
                $method = null;
                $this->_params['text'] = "{$asterix}{$value}{$asterix}\n{$this->_params['text']}";
            endif;

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
        $parse_mode = Arr::get ($this->_params, 'parse_mode');

        if ($parse_mode == 'MarkdownV2') {
            $text = str_replace (["\n", "_", "*", "[", "]", "(", ")", "~", "`", ">", "#", "+", "-", "=", "|", "{", "}", ".", "!"], [chr(10), "\_", "\*", "\[", "\]", "\(", "\)", "\~", "\`", "\>", "\#", "\+", "\-", "\=", "\|", "\{", "\}", "\.", "\!"], $text);
        }

        return $text;
    }

    public function send ()
    {
        if (empty($this->_params['chat_id'])) {
            throw new \InvalidArgumentException ('Chat Id not specified');
        }

        if (empty($this->_token)) {
            throw new \InvalidArgumentException ('Token not specified');
        }

        $request = CurlClient::post(sprintf (TelegramMessage::ENDPOINT, $this->_token), $this->_params)
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
