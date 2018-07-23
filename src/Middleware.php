<?php

namespace nulastudio;

class Middleware
{
    protected $destination;
    protected $goods;
    protected $through;
    protected $finish;

    public function __construct()
    {
        $this->goods   = [];
        $this->through = [];
    }

    public static function newInstance()
    {
        return new static();
    }

    public function send(...$goods)
    {
        $this->goods = $goods;
        return $this;
    }

    public function to(callable $destination)
    {
        // if (is_callable($destination)) {
        $this->destination = $destination;
        // } else {
        //     throw new \Exception('Unreachable destination.');
        // }
        return $this;
    }

    public function reset()
    {
        $this->through = [];
        return $this;
    }

    public function through($anywhere, ...$exparams)
    {
        @list($param1, $param2) = $exparams;
        if ($param1 === true || $param2 === true) {
            $this->reset();
        }
        if (is_callable($anywhere)) {
            if (is_string($param1)) {
                $this->through[$param1] = $anywhere;
            } else if (is_string($param2)) {
                $this->through[$param2] = $anywhere;
            } else {
                $this->through[] = $anywhere;
            }
        } elseif (is_array($anywhere)) {
            foreach ($anywhere as $name => $somewhere) {
                if (is_callable($somewhere)) {
                    if (is_string($name)) {
                        $this->through[$name] = $somewhere;
                    } else {
                        $this->through[] = $somewhere;
                    }
                } else {
                    trigger_error('Not valid callback.', E_USER_WARNING);
                }
            }
        } else {
            trigger_error('Not valid callback.', E_USER_WARNING);
            // throw new \Exception('Not valid callback.');
        }
        return $this;
    }

    public function finish(callable $todo)
    {
        $this->finish = $todo;
        return $this;
    }

    /**
     * 打包中间件成为一个匿名函数
     * 前置中间件: FIFO
     * 后置中间件: FILO
     * @return callable handler
     */
    public function pack()
    {
        if (!is_callable($this->destination)) {
            throw new \Exception('Unreachable destination.');
        }
        $handler = $this->destination;
        $params  = $this->goods;
        $finish  = $this->finish;
        foreach (array_reverse($this->through) as $next) {
            $handler = function (...$params) use ($next, $handler) {
                return $next($handler, ...$params);
            };
        }
        return function (...$override_params) use ($handler, $params, $finish) {
            if (count($override_params)) {
                $params = $override_params;
            }
            $return = $handler(...$params);
            return is_callable($finish) ? $finish($params, $return) : $return;
        };
    }

}
