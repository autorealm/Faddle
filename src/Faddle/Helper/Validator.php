<?php namespace Faddle\Helper;

use BadMethodCallException;
use DateTime;

/**
 * Validator
 */
class Validator {

	/**
	 * ���õ���֤����
	 *
	 * @type array
	 */
	public static $methods = array();

	/**
	 * ��֤�ַ���
	 *
	 * @type string
	 */
	protected $str;

	/**
	 * ��ʶĬ����֤���Ƿ����
	 *
	 * @type boolean
	 */
	protected static $default_added = false;

	public static $regex = array(
	//����
		'email'=>'/^[\w-\.]+@[\w-]+(\.(\w)+)*(\.(\w){2,4})$/',
	//�ֻ�����
		'mobile'=>'/^(?:13\d|15\d|18\d)-?\d{5}(\d{3}|\*{3})$/',
	//�̶��绰���ֻ���
		'tel'=>'/^((0\d{2,3})-)(\d{7,8})(-(\d{1,4}))?$/',
	//�̶��绰�����ֻ���
		'phone'=>'/^\d{3}-?\d{8}|\d{4}-?\d{7}$/',
	//����
		'domain'=>'/@([0-9a-z-_]+.)+[0-9a-z-_]+$/i',
	//����
		'date'=>'/^[1-9][0-9][0-9][0-9]-[0-9]{1,2}-[0-9]{1,2}$/',
	//����ʱ��
		'datetime'=>'/^[1-9][0-9][0-9][0-9]-[0-9]{1,2}-[0-9]{1,2} [0-9]{1,2}(:[0-9]{1,2}){1,2}$/',
	//ʱ��
		'time'=>'/^[0-9]{1,2}(:[0-9]{1,2}){1,2}$/',
	/*--------- �������� --------------*/
		'int'=>'/^\d{1,11}$/', //ʮ��������
		'hex'=>'/^0x[0-9a-f]+$/i', //16��������
		'bin'=>'/^[01]+$/', //������
		'oct'=>'/^0[1-7]*[0-7]+$/', //8����
		'float'=>'/^\d+\.[0-9]+$/', //������
	/*---------�ַ������� --------------*/
	//utf-8�����ַ���
		'chinese'=>'/^[\x{4e00}-\x{9fa5}]+$/u',
	/*---------�������� --------------*/
		'english' => '/^[a-z0-9_\.]+$/i', //Ӣ��
		'nickname' => '/^[\x{4e00}-\x{9fa5}a-z_\.]+$/ui', //�ǳƣ����Դ�Ӣ���ַ�������
		'realname' => '/^[\x{4e00}-\x{9fa5}]+$/u', //��ʵ����
		'password' => '/^[a-z0-9]{6,32}$/i', //����
		'area' => '/^0\d{2,3}$/', //����
		'version' => '/^\d+\.\d+\.\d+$/',       //�汾��
		'url' => '((https?)://(\S*?\.\S*?))([\s)\[\]{},;"\':<]|\.\s|$)', //URL
	);

	/**
	 * Sets up the validator chain with the string and optional error message
	 *
	 * @param string $str   The string to validate
	 */
	public function __construct($str) {
		$this->str = $str;
		if (!static::$default_added) {
			static::addDefault();
		}
	}

	/**
	 * Adds default validators on first use
	 *
	 * @return void
	 */
	public static function addDefault() {
		static::$methods['null'] = function ($str) {
			return $str === null || $str === '';
		};
		static::$methods['len'] = function ($str, $min, $max = null) {
			$len = strlen($str);
			return null === $max ? $len === $min : $len >= $min && $len <= $max;
		};
		static::$methods['int'] = function ($str) {
			return (string)$str === ((string)(int)$str);
		};
		static::$methods['float'] = function ($str) {
			return (string)$str === ((string)(float)$str);
		};
		static::$methods['text'] = function ($str) {
			return filter_var($str, FILTER_SANITIZE_STRING) !== false;
		};
		static::$methods['email'] = function ($str) {
			return filter_var($str, FILTER_VALIDATE_EMAIL) !== false;
		};
		static::$methods['url'] = function ($str) {
			return filter_var($str, FILTER_VALIDATE_URL) !== false;
		};
		static::$methods['ip'] = function ($str) {
			return filter_var($str, FILTER_VALIDATE_IP) !== false;
		};
		static::$methods['remoteip'] = function ($str) {
			return filter_var($str, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
		};
		static::$methods['alnum'] = function ($str) {
			return ctype_alnum($str);
		};
		static::$methods['alpha'] = function ($str) {
			return ctype_alpha($str);
		};
		static::$methods['contains'] = function ($str, $needle) {
			return strpos($str, $needle) !== false;
		};
		static::$methods['regex'] = function ($str, $pattern) {
			return preg_match($pattern, $str);
		};
		static::$methods['chars'] = function ($str, $chars) {
			return preg_match("/^[$chars]++$/i", $str);
		};
		static::$methods['data'] = function ($format, $value) {
			$date = DateTime::createFromFormat($format, $value);
			if ($date !== false) {
				$errors = DateTime::getLastErrors();
				if ($errors['error_count'] === 0 && $errors['warning_count'] === 0) {
					return $date->getTimestamp() > 0;
				}
			}
			return false;
		};
		static::$methods['equals'] = function ($field1, $field2) {
			if (! isset($field2)) {
				return false;
			}
			return $field1 === $field2;
		};
		static::$methods['chinese'] = function ($str) {
			$n =  preg_match("/^[".chr(0xa1)."-".chr(0xff)."]+$/", $str, $match);
			if ($n === 0) return false;
			else return true;
		};
		static::$methods['assic'] = function ($value) {
			$len = strlen($value);
			for ($i = 0; $i < $len; $i++) {
				$ord = ord(substr($value, $i, 1));
				if ($ord > 127) return false;
			}
			return true;
		};
		static::$methods['integer'] = function ($field) {
			if (is_string($field)) {
				if ($field[0] === '-') {
					return ctype_digit(substr($field, 1));
				}
				return ctype_digit($field);
			} else {
				return is_int($field);
			}
		};
		static::$methods['length'] = function ($field, $min, $max) {
			$length = mb_strlen($field, 'UTF-8');
			return $length >= $min && $length <= $max;
		};
		static::$methods['range'] = function ($field, $min, $max) {
			if (! is_numeric($field)) {
				return false;
			}
			if ($field < $min || $field > $max) {
				return false;
			}
		};
		
		static::$default_added = true;
	}

	/**
	 * ���һ���Զ������֤����
	 *
	 * @param string $method        ��֤����������
	 * @param callable $callback    �ص�����
	 * @return void
	 */
	public static function addValidator($method, $callback) {
		static::$methods[strtolower($method)] = $callback;
	}

	/**
	 * ħ������ "__call"
	 *
	 * ������ӷ���ǰ׺'is'��'no'����ת��֤�����صĽ��ֵ
	 *
	 * @param string $method            ִ�е���֤��������
	 * @param array $args               ��֤������Ҫ�Ĳ���
	 * @throws BadMethodCallException   
	 * @return Validator|boolean
	 */
	public function __call($method, $args) {
		$reverse = false;
		$validator = $method;
		$method_substr = substr($method, 0, 2);
		
		if ($method_substr === 'is') {       // is<$validator>()
			$validator = substr($method, 2);
		} elseif ($method_substr === 'no') { // not<$validator>()
			$validator = substr($method, 3);
			$reverse = true;
		}
		
		$validator = strtolower($validator);
		
		if (!$validator || !isset(static::$methods[$validator])) {
			throw new BadMethodCallException('Unknown method '. $method .'()');
		}
		
		$validator = static::$methods[$validator];
		array_unshift($args, $this->str);
		
		switch (count($args)) {
			case 1:
				$result = $validator($args[0]);
				break;
			case 2:
				$result = $validator($args[0], $args[1]);
				break;
			case 3:
				$result = $validator($args[0], $args[1], $args[2]);
				break;
			case 4:
				$result = $validator($args[0], $args[1], $args[2], $args[3]);
				break;
			default:
				$result = call_user_func_array($validator, $args);
				break;
		}
		
		$result = (bool)($result ^ $reverse);
		
		if ($result !== false) {
			return $result;
		} else {
			return false;
		}
		
		return $this;
	}

}
