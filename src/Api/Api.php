<?php

namespace SaphyrWebGenerator\Api;

use SaphyrWebGenerator\Api\QueryBuilder;

class Api
{
	/**
	 * @var string client name
	 */
	protected $client;
    /**
     * @var string domain
     */
    protected $domain;
	/**
	 * @var string token
	 */
	protected $token;
	/**
	 * @var string|null private key
	 */
	protected $private;
	/**
	 * @var int|mixed TTL for cache
	 */
	protected $ttl = 30;

	protected $user;
	protected $pubKey;
	protected $secret;
	protected $debug = false;
	protected $tempStorage;
	protected $webRoot;
	protected $token_ttl = (60 * 60 * 24);

	/**
	 * @param array $settings
	 * @param string $pubKey
	 * @param string $private
	 * @param int $ttl
	 * @throws \Exception
	 */
	function __construct($settings)
	{
		if (!isset($settings['client'])) {
			throw new \Exception('Client name is required');
		}
		$this->client = $settings['client'];
		$this->domain = $settings['domain'] ?? "saphyr-solutions.com";
		$this->pubKey = $settings['pubKey'] ?? null;
		$this->private = $settings['privKey'] ?? null;
		$this->ttl = $settings['ttl'] ?? null;
		$this->user = $settings['user'] ?? null;
		$this->secret = $settings['secret'] ?? null;
		$this->debug = $settings['debug'] ?? null;
		$this->tempStorage = $settings['tempStorage'] ?? '/tmp/';
		$this->webRoot = $settings['webRoot'] ?? '/';


        if (!is_dir ($this->tempStorage)) {
            mkdir($this->tempStorage, 0755);
        }


        if (!file_exists($this->tempStorage . '/.htaccess')) {
            file_put_contents($this->tempStorage . '/.htaccess', "<Files \"*.json\">
    Order Deny,Allow
    Deny from all
</Files>");
		}


		return $this;
	}


	public function setTempStorate($path)
	{
		$this->tempStorage = $path;
	}

	public function setUser($user)
	{
		$this->user = $user;
		return $this;
	}

	public function setDebug($bool)
	{
		$this->debug = $bool;
		return $this;
	}

	public function setSecret($pass)
	{
		$this->secret = $pass;
		return $this;
	}

	public function setPublicKey($key)
	{
		$this->pubKey = $key;
		return $this;
	}

    public function getPublicKey()
    {
        return $this->pubKey;
    }

	public function setPrivateKey($key)
	{
		$this->private = $key;
		return $this;
	}

    public function getPrivateKey()
    {
        return $this->private;
    }

	public function setToken($token)
	{
		$this->token = $token;
		return $this;
	}

	/**
	 * Get Modules List in array
	 * Return for each module  id,slug,name
	 * @return array
	 */
	public function getModulesList()
	{
		try {
			$modules = $this->getModules();
			$list = [];
			foreach ($modules["results"] as $module) {
				$list[] = ['id' => $module["id"], 'slug' => $module["slug"], 'name' => $module["name"]];
			}
			return $list;
		} catch (\Exception $e) {
			die($e->getMessage());
		}

	}

	/**
	 * Get All Elements for module ID
	 * Return object from json
	 * @param int $moduleId
	 * @return array
	 * @throws \Exception
	 */
	public function getModuleElements($moduleId)
	{
		try {
			$module = $this->getModuleInfos($moduleId);
			if (!$module) {
				throw new \Exception('Module ' . $moduleId . ' not found');
			}
			$lastModuleMod = strtotime($module['modification_date']);
			$file = __FUNCTION__ . '_' . $moduleId . '.json';
			if ($this->checkExpired($file, $lastModuleMod) || isset($_GET['test'])) {
				$result = $this->call('/api/v1/module/' . $moduleId . '/element', 'GET',
					["byReference" => "1", "page" => "1", "perPage" => "0"]);
				if ($result) {
					$this->cacheSave($file, $result);
				}
			}
			return ($this->getFromCache($file));
		} catch (\Exception $e) {
			die($e->getMessage());
		}

	}

	/**
	 * Get Module Infos
	 * @param int $moduleId
	 * @return array
	 */
	public function getModuleInfos($moduleId)
	{
		try {
			$file = __FUNCTION__ . '_' . $moduleId . '.json';

			if ($this->checkExpired($file)) {
				$result = $this->call('/api/v1/module/' . $moduleId);
				if ($result) {
					$this->cacheSave($file, $result);
				}
			}
			return ($this->getFromCache($file));
		} catch (\Exception $e) {
			die($e->getMessage());
		}
	}


	/**
	 * Get Modules list
	 * @return object
	 * @throws \Exception
	 */
	public function getModules(): array
	{
		try {
			$file = __FUNCTION__ . '.json';
			if ($this->checkExpired($file)) {
				$result = $this->call('/api/v1/module');
				if ($result) {
					$this->cacheSave($file, $result);
				}
			}
			return ($this->getFromCache($file));
		} catch (\Exception $e) {
			die($e->getMessage());
		}
	}

	/**
	 * @param array $request
	 * @return array
	 * @throws \Exception
	 */
	public function getItems(array $request): array
	{

		$module = $request['module'] ?? null;
		if (!$module) {
			throw new \Exception('Module ID/Name is required!');
		}
		$contenu = $this->getModuleElements($module)["results"];

		$fields = $this->getModuleElementFields($module)["results"];
		$query = new QueryBuilder();
		$query->setModule($module);
		$query->setFields($fields);
		$query->addFilter($request['filter']);

		if (isset($request['order'])) {
			foreach ($request['order'] as $order) {
				$query->addOrder($order[0], $order[1]);
			}
		}

		if (isset($request['limit']) && is_array($request['limit'])) {
			$query->setLimit($request['limit'][0], $request['limit'][1]);
		}
		return $query->getResults($contenu);

	}

	public function getModuleElementField($moduleId, $fieldId)
	{
		$fields = $this->getModuleElementFields($moduleId)["results"];
		$field = array_filter($fields, function ($f) use ($fieldId) {
			if (isset($f['config']['name']) && $f['config']['name'] == $fieldId) {
				return true;
			} elseif (isset($f['config']['reference']) && $f['config']['reference'] == $fieldId) {
				return true;
			} elseif (isset($f['id']) && $f['id'] == $fieldId) {
				return true;
			}
			return false;
		});
		if ($field) {
			return array_shift($field);
		}
		return false;

	}

	public function getModuleElementFieldConfig($moduleId, $fieldId)
	{
		$field = $this->getModuleElementField($moduleId, $fieldId);
		if ($field) {
			return ($field["config"]);
		}
		return [];

	}

	/**
	 * @param $moduleId
	 * @return array|false|string|void
	 */
	public function getModuleElementFields($moduleId)
	{
		try {
			$file = __FUNCTION__ . '_' . $moduleId . '.json';
			if ($this->checkExpired($file)) {
				$result = $this->call('/api/v1/module/' . $moduleId . '/element/fields');
				if ($result) {
					$this->cacheSave($file, $result);
				}
			}

			$result = $this->getFromCache($file);
			if (is_array($result)) {
				return array_filter($result, function ($e) {
					if(!isset($e['type'])) return true;
					return !in_array($e['type'], ['Tab', 'Tabs']);
				});
			}
			return null;
		} catch (\Exception $e) {
			die($e->getMessage());
		}
	}

	/**
	 * @param $moduleId
	 * @return array|false|string|void
	 */
	public function getModuleElementformConfig($moduleId, $miniFormId = 0)
	{
		try {
			$file = __FUNCTION__ . '_' . $moduleId . '.json';
			if ($miniFormId) {
				$file = __FUNCTION__ . '_' . $moduleId . '_' . $miniFormId . '.json';
			}
			if ($this->checkExpired($file)) {
				$params = [];
				if ($miniFormId) {
					$params['miniFormId'] = $miniFormId;
					$params['isFor'] = 'form';
				}
				$result = $this->call('/api/v1/module/' . $moduleId . '/element/formConfig', 'get',$params);
				if ($result) {
					$this->cacheSave($file, $result);
				}
			}
			return ($this->getFromCache($file));
		} catch (\Exception $e) {
			die($e->getMessage());
		}
	}


	public function getMedia($array, $params = null)
	{

		$extra_storage = '';
		if (isset($params) && is_array($params)) {
			if (isset($params['width'])) {
				$extra_storage .= '_w' . intval($params['width']);
			}
			if (isset($params['height'])) {
				$extra_storage .= '_h' . intval($params['height']);
			}

		}
		$webp=false;
		$uri= $this->webRoot . '__SC__' . $array['id'] . $extra_storage . '_' . $array['server_name'];
		$path = $this->tempStorage . '__SC__' . $array['id'] . $extra_storage . '_' . $array['server_name'];
		if(in_array($array['extension'],['png','jpg','jpeg'])) {
			$webp=true;
			$params['webp']=1;


		}
		if($webp) {
			$path=str_replace('.'.$array['extension'],'.webp',$path);
			$uri = str_replace('.'.$array['extension'],'.webp',$path);
		}


		if (isset($array['modification_date']) && strtotime($array['modification_date']) > time() + $this->ttl) {
			@unlink($path);
		}

		if (!file_exists($path)) {
			try {
				$result = $this->call('/api/v1/file/' . $array['id'], 'GET', $params);
				if($webp && !$result) {
					// Fallback si le webp n'est pas trouvé
					unset($params['webp']);
					$result = $this->call('/api/v1/file/' . $array['id'], 'GET', $params);
					$uri= $this->webRoot . '__SC__' . $array['id'] . $extra_storage . '_' . $array['server_name'];
					$path = $this->tempStorage . '__SC__' . $array['id'] . $extra_storage . '_' . $array['server_name'];

				}
				$file = fopen($path, "wb");
				fwrite($file, $result);
				fclose($file);
			} catch (\Exception $e) {
				die($e->getMessage());
			}
		}

		return $uri;
	}

	public function deleteElement($moduleId, $uniqueId)
	{
		try {
			$result = $this->call('/api/v1/module/' . $moduleId . '/element/' . $uniqueId, 'DELETE');
			return $result;
		} catch (\Exception $e) {
			die($e->getMessage());
		}

	}

	public function updateElement($moduleId, $uniqueId, $datas)
	{
		try {
			$result = $this->call('/api/v1/module/' . $moduleId . '/element/' . $uniqueId, 'PUT', $datas);
			return $result;
		} catch (\Exception $e) {
			die($e->getMessage());
		}
	}

	public function addElement($moduleId, $datas)
	{
		try {
			$result = $this->call('/api/v1/module/' . $moduleId . '/element', 'POST', $datas);
			return $result;
		} catch (\Exception $e) {
			die($e->getMessage());
		}
	}

	public function isValid($moduleId, $uniqueId, $datas)
	{
		try {
			$result = $this->call('/api/v1/module/' . $moduleId . '/element/' . $uniqueId . '/isValid', 'GET', $datas);
			return $result;
		} catch (\Exception $e) {
			die($e->getMessage());
		}
	}

    public function callEndpoint($endpoint, $method, $datas)
    {
        try {
            $result = $this->call($endpoint, $method, $datas);
            return $result;
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

	/**
	 * Save content to Saphyr cache
	 * @param $file
	 * @param $content
	 */
	private function cacheSave($file, $content)
	{
		if (!empty($content)) {
			$path = $this->tempStorage . '__SC__' . $this->client . '_' . $file;

			$tempfile = $path . uniqid(rand(), true);
			file_put_contents($tempfile, json_encode($content), LOCK_EX);
			@unlink($path);
			rename($tempfile, $path);
		}
	}

	/**
	 * Get content from Saphyr cache
	 * @param $file
	 * @return false|string
	 */
	private function getFromCache($file)
	{
		$path = $this->tempStorage . '__SC__' . $this->client . '_' . $file;
		if (!file_exists($path)) {
			return [];

		}


		$result =json_decode(file_get_contents($path), true);
		if(!is_array($result)) return null;
		return $result;
	}

	protected function file_get_contents_locking($filename)
	{
		$file = fopen($filename, 'rb');
		if ($file === false) {
			return false;
		}
		$lock = flock($file, LOCK_SH);
		if (!$lock) {
			fclose($file);
			return false;
		}
		$string = '';
		while (!feof($file)) {
			$string .= fread($file, 8192);
		}
		flock($file, LOCK_UN);
		fclose($file);
		return $string;
	}

	/**
	 * @param $file
	 */
	private function clearStorage($file)
	{
		$path = $this->tempStorage . '__SC__' . $this->client . '_' . $file;
		if (file_exists($path)) {
			@unlink($path);
		}
	}

	/**
	 * Check if request is avalaible in cache or not
	 * @param $file
	 * @param null $lastItemMod
	 * @return bool
	 */
	private function checkExpired($file, $lastItemMod = null)
	{
		if ($this->debug) {
			echo "<pre>" . PHP_EOL;
		}

		$path = $this->tempStorage . '__SC__' . $this->client . '_' . $file;
		if (!file_exists($path)) {
			if ($this->debug) {
				echo $file . " not found on server\n" . PHP_EOL;
			}
			return true;
		}

		if (!filesize($path)) {
			return true;
		}


		$lastMod = filemtime($path);
		if ($lastMod >= strtotime('-' . $this->ttl . ' second')) {
			if ($this->debug) {
				echo "Cache OK for " . $file . " : date (" . date('Y-m-d H:i:s',
						$lastMod) . ") within TTL (" . $this->ttl . " seconds)\n" . PHP_EOL;
			}
			return false;
		}

		if ($lastItemMod && $lastMod >= $lastItemMod) {
			if ($this->debug) {
				echo "Cache OK for " . $file . " : Module date (" . date('Y-m-d H:i:s',
						$lastItemMod) . ") older than file (" . date('Y-m-d H:i:s', $lastMod) . ")\n" . PHP_EOL;
			}
			return false;
		}

		if ($this->debug) {
			echo "</pre>" . PHP_EOL;
		}


		return true;
	}

    /**
     * @param string $mode REMOTE OR SERVER
     * @return bool
     */
    public static function _isLocalhost($mode = "REMOTE")
    {
        $whitelist = array("127.0.0.1", "::1", "localhost");
        return in_array($_SERVER[$mode . '_ADDR'], $whitelist);
    }


	/**
	 * @param $method
	 * @param null $params
	 * @return mixed
	 * @throws \Exception
	 */
	private function call($action, $method = 'get', $params = null)
	{
		$method = strtoupper($method);
		if (!trim($method)) {
			$method = 'GET';
		}

		$headers = [];

		$url = (self::_isLocalhost() ? "http" : "https") . '://' . $this->client . '.'.$this->domain . $action;
		if ($this->token) {
			$headers = ['token: ' . $this->token];
		}

		if (in_array($method, ['PUT'])) {
			$params['_METHOD'] = 'PUT';
		}

		if ($this->private) {
			$signature = $this->getSignature($params, $action);
			$headers[] = 'signature: ' . $signature;
		}

		if (in_array($method, ['GET'])) {
			if (!empty($params)) {
				$query = http_build_query($params);
				if ($query) {
					$url .= '?' . $query;
				}
			}
		}
		$ch = curl_init();
		if ($this->debug) {
			curl_setopt($ch, CURLOPT_VERBOSE, true);
			$verbose = fopen('php://temp', 'w+');
			curl_setopt($ch, CURLOPT_STDERR, $verbose);
		}
		if (self::_isLocalhost("SERVER")) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}
		if (in_array($method, ['POST', 'PUT'])) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		}
		if (in_array($method, ['DELETE'])) {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if ($headers && count($headers)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}


		if (!$result = curl_exec($ch)) {
			trigger_error(curl_error($ch));
		}

		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

		if ($httpCode < 200 || $httpCode > 299) {
			error_log('api call ' . $action . ' returns '.$httpCode);
			return null;
		}

		if ($this->debug) {
			rewind($verbose);
			$verboseLog = stream_get_contents($verbose);

			echo "Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n";
			print_r($result);
		}
		if ($result) {
			$return = json_decode($result, true);

			if (isset($return["error"]) && isset($return["error"]["message"])) {
                if ($this->debug)var_dump($return);
				throw  new \Exception($return["error"]["message"]);
			}
			if (is_array($return) || is_object($return)) {
				return $return;
			}
			return $result;
		} else {
			return null;
		}


	}

	/**
	 * Build signature header from params
	 * @param null $params
	 * @return false|string
	 */
	private function getSignature($params = null, $action = '')
	{
		$signContent = "";
		if ($params && is_array($params)) {
			self::_ksort_deep($params);

			array_walk_recursive($params, function ($item) use (&$signContent) {
				$signContent .= $item . "+";
			});
		}

		$signContent .= $this->token;
		$signContent .= "+" . explode("?", $action, 2)[0];

		$sign = hash_hmac("sha256", $signContent, $this->private);
		return $sign;
	}

	/**
	 * Sort array deep nby key
	 * @param $array
	 */
	private static function _ksort_deep(&$array)
	{
		ksort($array);
		foreach ($array as $k => $v) {
			if (is_array($v)) {
				self::_ksort_deep($array[$k]);
			}
		}
	}

	/**
	 * @return mixed
	 * @throws \Exception
	 */

	public function getToken()
	{
		$ttl = $this->ttl;
		$this->ttl = $this->token_ttl;
		try {

			$file = __FUNCTION__ . '_token.json';

			if ($this->checkExpired($file)) {
				$params = ['public_key' => $this->pubKey, 'user_login' => $this->user, 'user_secret' => $this->secret];
				$result = $this->call('/api/v1/token', 'get', $params);
				if (isset($result["token"])) {
					$this->token = $result["token"];
				} else {
					$this->ttl = $ttl;
					$this->clearStorage($file);
					return null;
				}
				if ($result) {
					$this->cacheSave($file, $result);
				}
			}
			$result = ($this->getFromCache($file));
			$this->token = $result['token'];
			$this->ttl = $ttl;
			return $result['token'];
		} catch (\Exception $e) {
			$this->ttl = $ttl;
			die($e->getMessage());
		}
	}
}