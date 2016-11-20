<?php namespace Faddle;

use Exception;
use ErrorException;
use InvalidArgumentException;
use ArrayAccess;

/**
 * Faddle ������
 *
 * @package    Faddle
 * @author     KYO
 */
class Config implements ArrayAccess {

	/**
	 * ���������������
	 *
	 * @var array|null
	 */
	protected $data = null;

	/**
	 * �������������
	 *
	 * @var array
	 */
	protected $cache = array();


	/**
	 * ���õĹ��캯��
	 *
	 * @param  string|array $path �����ļ���·��
	 */
	public function __construct($path) {
		$paths = $this->getValidPath($path);
		$this->data = array();
		foreach ($paths as $path) {
			// Try and load file
			$this->data = array_replace_recursive($this->data, $this->getConfigFileData($path));
		}
	}

	/**
	 * ����һ�����õľ�̬����
	 *
	 * @param  string|array $path
	 * @return Config
	 */
	public static function load($path) {
		return new static($path);
	}

	/**
	 * ��֤·���Ƿ��ļ���Ŀ¼�������顣�����ļ��б����顣
	 *
	 * @param  string|array $path
	 * @return array
	 * @throws Exception   ���·��������
	 */
	private function getValidPath($path) {
		if (empty($path)) return array();
		if (is_array($path)) {
			$paths = array();
			foreach ($path as $unverifiedPath) {
				try {
					if ($unverifiedPath[0] !== '?') {
						$paths = array_merge($paths, $this->getValidPath($unverifiedPath));
						continue;
					}
					$optionalPath = ltrim($unverifiedPath, '?');
					$paths = array_merge($paths, $this->getValidPath($optionalPath));
				} catch (Exception $e) {
					// If `$unverifiedPath` is optional, then skip it
					if ($unverifiedPath[0] === '?') {
						continue;
					}
					// Otherwise rethrow the exception
					throw $e;
				}
			}
			return $paths;
		}
		//·����Ŀ¼
		if (is_dir($path)) {
			$paths = glob($path . '/*.*'); //ȡ��Ŀ¼�������ļ�
			if (empty($paths)) {
				throw new Exception("Configuration directory: [$path] is empty");
			}
			return $paths;
		}
		//���·��Ҳ���Ǵ��ڵ��ļ������׳��쳣��
		if (!file_exists($path)) {
			throw new Exception("Configuration file: [$path] cannot be found");
		}
		return array($path);
	}

	/**
	 * ���������ļ�
	 *
	 * @param  string $path �����ļ�·��
	 * @return Noodlehaus\File\FileInterface
	 */
	private function getConfigFileData($path) {
		if (!is_file($path)) {
			throw new InvalidArgumentException(sprintf('The file %s not exists!', $path));
		}
		$info = pathinfo($path);
		$extension = isset($info['extension']) ? $info['extension'] : '';
		//������չ��������Ӧ��������
		switch (strtolower($extension)) {
		case 'ini':
			$data = @parse_ini_file($path, true);
			if (!$data) {
				$error = error_get_last();
				throw new ErrorException($error);
			}
			break;
		case 'xml':
			libxml_use_internal_errors(true);
			$data = simplexml_load_file($path, null, LIBXML_NOERROR);
			if ($data === false) {
				$errors      = libxml_get_errors();
				$latestError = array_pop($errors);
				throw new ErrorException($latestError);
			}
			$data = json_decode(json_encode($data), true);
			break;
		case 'php':
			// Require the file, if it throws an exception, rethrow it
			try {
				$data = require $path;
			} catch (Exception $exception) {
				throw $exception;
			}
			// If we have a callable, run it and expect an array back
			if (is_callable($data)) {
				$data = call_user_func($data);
			}
			// Check for array, if its anything else, throw an exception
			if (!$data || !is_array($data)) {
				throw new Exception('PHP file does not return an array');
			}
			break;
		case 'json':
			$data = json_decode(file_get_contents($path), true);
			if (function_exists('json_last_error_msg')) {
				$error_message = json_last_error_msg();
			} else {
				$error_message  = 'Syntax error';
			}
			if (json_last_error() !== JSON_ERROR_NONE) {
				$error = array(
					'message' => $error_message,
					'type'    => json_last_error(),
					'file'    => $path,
				);
				throw new Exception($error);
			}
			break;
		default:
			$data = null;
		}

		return $data;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get($key, $default=null) {
		// Check if already cached
		//if (isset($this->cache[$key])) {
			//return $this->cache[$key];
		//}
		$segs = explode('.', $key);
		$root = $this->data;
		// nested case
		foreach ($segs as $part) {
			if (isset($root[$part])) {
				$root = $root[$part];
				continue;
			} else {
				$root = $default;
				break;
			}
		}
		//$this->cache[$key] = $root;
		return $root;
	}

	/**
	 * {@inheritDoc}
	 */
	public function set($key, $value) {
		$segs = explode('.', $key);
		$root = &$this->data;
		// Look for the key, creating nested keys if needed
		while ($part = array_shift($segs)) {
			if (!isset($root[$part]) && count($segs)) {
				$root[$part] = array();
			}
			$root = &$root[$part];
		}
		// Assign value at target node
		$root = $value;
		//$this->cache[$key] = $value;
	}

	/**
	 * Search for an Config value. Returns TRUE if the key exists and FALSE if not.
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function has($key) {
		$segments = explode('.', $key);
		$array = $this->data;
		foreach ($segments as $segment) {
			if (! is_array($array) || ! isset($array[$segment])) {
				return false;
			}
			$array = $array[$segment];
		}
		return true;
	}

	/**
	 * Remove an Confing value.
	 * 
	 * @param   string $key  Config key
	 * @return  boolean
	 */
	public function remove($key) {
		$segments = explode('.', $key);
		$array = &$this->data;
		while (count($segments) > 1) {
			$segment = array_shift($segments);
			if (! isset( $array[$segment] ) || ! is_array($array[$segment])) {
				return false;
			}
			$array = &$array[$segment];
		}
		unset($array[array_shift($segments)]);
		if (isset($this->cache[$key])) {
			unset($this->cache[$key]);
		}
		return true;
	}

	/**
	 * Returns the values from a single column, identified by the key.
	 * 
	 * @param   string $key   Array key
	 * @return  array
	 */
	public function value($key) {
		$array = $this->data;
		return array_map(function ($value) use ($key) {
			return is_object($value) ? $value->$key : $value[$key];
		}, $array);
	}

	/**
	 * Returns TRUE if the Config data is associative and FALSE if not.
	 * 
	 * @return  boolean
	 */
	public function isAssoc(){
		$array = $this->data;
		return count(array_filter(array_keys($array), 'is_string')) === count($array);
	}

	/**
	 * Gets a value using the offset as a key
	 *
	 * @param  string $offset
	 * @return mixed
	 */
	public function offsetGet($offset) {
		return $this->get($offset);
	}

	/**
	 * Checks if a key exists
	 *
	 * @param  string $offset
	 * @return bool
	 */
	public function offsetExists($offset) {
		return !is_null($this->get($offset));
	}

	/**
	 * Sets a value using the offset as a key
	 *
	 * @param  string $offset
	 * @param  mixed  $value
	 * @return void
	 */
	public function offsetSet($offset, $value) {
		$this->set($offset, $value);
	}

	/**
	 * Deletes a key and its value
	 *
	 * @param  string $offset
	 * @return void
	 */
	public function offsetUnset($offset) {
		$this->set($offset, null);
	}

}
