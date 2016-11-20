<?PHP namespace Faddle\Middleware;

use Closure ;
use Faddle\App;
use Faddle\Http\Request as Request;
use Faddle\Http\Response as Response;

/**
 * �м������
 */
abstract class BaseMiddleware {
	public $app = null;
	public $input = null;
	public $output = null;
	protected $next = null;
	
	/**
	 * ���������� run ���������ݡ�
	 * 
	 * @param mixed... ��������
	 */
	public function handle() {
		$this->app = App::getInstance();
		$this->input = Request::getInstance();
		$this->output = Response::getInstance();
		$args = func_get_args();
		$result = self::call($this->input, $this->output, $this, $args);
		return $this->next($result);
	}
	
	/**
	 * ������һ���м�������� handle ������
	 */
	public function setNext($middleware) {
		$this->next = $middleware;
	}
	
	public function getNext() {
		return $this->next;
	}
	
	/**
	 * ִ�а󶨵���һ�м������ handle ��������
	 * @return mixed
	 */
	protected function next($args=null) {
		if (is_null($this->next)) return $args; //���򷵻�
		if (method_exists($this->next, 'handle'))
			return call_user_func_array(array($this->next, 'handle'), $args);
		if (is_callable($this->next))
			return self::call($this->input, $this->output, $this->next, $args);
		else return $args;
	}
	
	protected static function call($input, $output, callable $middleware, $args=null) {
		$args = array_merge(array($input, $output, 
			function($request, $response) {
				if ($response instanceof Response and $response != Response::getInstance()) {
					Response::setInstance($response);
				}
				//next?
			}), $args);
		return call_user_func_array($middleware, $args);
	}
	
	/**
	 * ִ���м����
	 * 
	 * @param array $middlewares
	 */
	public static function run(array $middlewares, $request, $response, $factory=null) {
		static $_factory;
		if ($factory) $_factory = $factory;
		if (! $request) $request = Request::getInstance();
		if (! $response) $response = Response::getInstance();
		if (!$middlewares or empty($middlewares)) return;
		
		do {
			$middleware = array_shift($middlewares);
			if (!is_callable($middleware)) {
				$middleware = call_user_func($_factory, $middleware);
			}
		} while (!is_callable($middleware) and !empty($middlewares));
		
		call_user_func($middleware, $request, $response, 
				function($request, $response) use ($middlewares) {
					self::run($middlewares, $request, $response);
				}
			);
	}
	
	abstract public function __invoke(Request $request, Response $response, callable $callable=null);
	
}