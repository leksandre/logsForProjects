<?php
/**
 * CLogFilter class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * CLogFilter preprocesses the logged messages before they are handled by a log route.
 *
 * CLogFilter is meant to be used by a log route to preprocess the logged messages
 * before they are handled by the route. The default implementation of CLogFilter
 * appends additional context information to the logged messages. In particular,
 * by setting {@link logVars}, predefined PHP variables such as
 * $_SERVER, $_POST, etc. can be saved as a log message, which may help identify/debug
 * issues encountered.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.logging
 */
class DatadogLogFilter extends CLogFilter {
    
    /**
     * @var array list of the PHP predefined variables that should be logged.
     * Note that a variable must be accessible via $GLOBALS. Otherwise it won't be logged.
     */
    public $logVars=array('_GET','_POST','_FILES','_COOKIE','_SESSION'); // REMOVED _SERVER
    
    /**
     * Filters the given log messages.
     * This is the main method of CLogFilter. It processes the log messages
     * by adding context information, etc.
     *
     * @param array $logs the log messages
     *
     * @return array
     */
    public function filter(&$logs) {
        if (!empty($logs)) {
            if (($message = $this->getContext()) !== '') {
                foreach ($logs as &$log)
                {
                    $log[] = $message;
                }
            }
            $this->format($logs);
        }
        return $logs;
    }
    
    
    
    /**
     * Generates the context information to be logged.
     * The default implementation will dump user information, system variables, etc.
     *
     * @return string the context information. If an empty string, it means no context information.
     */
    protected function getContext() {
        $context = [];
        if ($this->logUser && ($user = Yii::app()
                ->getComponent('user', false)) !== null
        ) {
            $context['User'] = $user->getName() . ' (ID: ' . $user->getId() . ')';
        }
        
        
        foreach ($this->logVars as $name) {
            if (($value = $this->getGlobalsValue($name)) !== null) {
                $context["\${$name}"] = $value;
            }
        }
        
        return $context;
    }
    
    /**
     * @param string[] $path
     *
     * @return string|null
     */
    private function getGlobalsValue(&$path) {
        if (is_scalar($path)) {
            return !empty($GLOBALS[$path]) ? $GLOBALS[$path] : null;
        }
        $pathAux = $path;
        $parts = [];
        $value = $GLOBALS;
        do {
            $value = $value[$parts[] = array_shift($pathAux)];
        } while (!empty($value) && !empty($pathAux) && !is_string($value));
        $path = implode('.', $parts);
        
        return $value;
    }
}
