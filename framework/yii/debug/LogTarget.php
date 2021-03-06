<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\debug;

use Yii;
use yii\log\Target;

/**
 * The debug LogTarget is used to store logs for later use in the debugger tool
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class LogTarget extends Target
{
	/**
	 * @var Module
	 */
	public $module;
	public $tag;

	/**
	 * @param \yii\debug\Module $module
	 * @param array $config
	 */
	public function __construct($module, $config = array())
	{
		parent::__construct($config);
		$this->module = $module;
		$this->tag = uniqid();
	}

	/**
	 * Exports log messages to a specific destination.
	 * Child classes must implement this method.
	 */
	public function export()
	{
		$path = $this->module->dataPath;
		if (!is_dir($path)) {
			mkdir($path);
		}
		$indexFile = "$path/index.json";
		if (!is_file($indexFile)) {
			$manifest = array();
		} else {
			$manifest = json_decode(file_get_contents($indexFile), true);
		}
		$request = Yii::$app->getRequest();
		$manifest[$this->tag] = $summary = array(
			'tag' => $this->tag,
			'url' => $request->getAbsoluteUrl(),
			'ajax' => $request->getIsAjax(),
			'method' => $request->getMethod(),
			'ip' => $request->getUserIP(),
			'time' => time(),
		);
		$this->gc($manifest);

		$dataFile = "$path/{$this->tag}.json";
		$data = array();
		foreach ($this->module->panels as $id => $panel) {
			$data[$id] = $panel->save();
		}
		$data['summary'] = $summary;
		file_put_contents($dataFile, json_encode($data));
		file_put_contents($indexFile, json_encode($manifest));
	}

	/**
	 * Processes the given log messages.
	 * This method will filter the given messages with [[levels]] and [[categories]].
	 * And if requested, it will also export the filtering result to specific medium (e.g. email).
	 * @param array $messages log messages to be processed. See [[Logger::messages]] for the structure
	 * of each message.
	 * @param boolean $final whether this method is called at the end of the current application
	 */
	public function collect($messages, $final)
	{
		$this->messages = array_merge($this->messages, $messages);
		if ($final) {
			$this->export($this->messages);
		}
	}

	protected function gc(&$manifest)
	{
		if (count($manifest) > $this->module->historySize + 10) {
			$n = count($manifest) - $this->module->historySize;
			foreach (array_keys($manifest) as $tag) {
				$file = $this->module->dataPath . "/$tag.json";
				@unlink($file);
				unset($manifest[$tag]);
				if (--$n <= 0) {
					break;
				}
			}
		}
	}
}
