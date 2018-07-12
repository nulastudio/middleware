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

    public function through($anywhere, bool $reset = false)
    {
        if ($reset) {
            $this->through = [];
        }
        if (is_callable($anywhere)) {
            $this->through[] = $anywhere;
        } elseif (is_array($anywhere)) {
            foreach ($anywhere as $somewhere) {
                if (is_callable($somewhere)) {
                    $this->through[] = $somewhere;
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
