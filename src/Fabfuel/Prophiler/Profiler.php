<?php
/**
 * @author @fabfuel <fabian@fabfuel.de>
 * @created 13.11.14, 07:47
 */
namespace Fabfuel\Prophiler;

use Fabfuel\Prophiler\Benchmark\BenchmarkFactory;
use Fabfuel\Prophiler\Benchmark\BenchmarkInterface;
use Fabfuel\Prophiler\Exception\UnknownBenchmarkException;

class Profiler implements ProfilerInterface, \Countable
{
    /**
     * @var BenchmarkInterface[]
     */
    protected $benchmarks = [];

    /**
     * @var double Starting time
     */
    protected $start;

    /**
     * Save start time on construction
     */
    public function __construct()
    {
        $this->start = (double)microtime(true);
    }

    /**
     * Start a new benchmark
     *
     * @param string $name Unique identifier like e.g. Class::Method (\Foobar\MyClass::doSomething)
     * @param array $metadata Additional metadata
     * @param string $component Name of the component which triggered the benchmark, e.g. "App", "Database"
     * @return string Benchmark identifier token
     */
    public function start($name, array $metadata = [], $component = null)
    {
        $benchmark = BenchmarkFactory::getBenchmark(
            $name,
            $metadata,
            $component
        );
        $benchmark->start();
        return $this->addBenchmark($benchmark);
    }

    /**
     * Stop a running benchmark
     * If no token provided, the last started benchmark is stopped
     *
     * @param string $token Benchmark identifier
     * @param array $metadata Additional metadata
     * @return BenchmarkInterface $benchmark
     * @throws UnknownBenchmarkException
     */
    public function stop($token = null, array $metadata = [])
    {
        $benchmark = $this->getBenchmark($token);
        $benchmark->addMetadata($metadata);
        $benchmark->stop();
        return $benchmark;
    }

    /**
     * @param BenchmarkInterface $benchmark
     * @return string
     */
    public function addBenchmark(BenchmarkInterface $benchmark)
    {
        $token = spl_object_hash($benchmark);
        $this->benchmarks[$token] = $benchmark;
        return $token;
    }

    /**
     * Get the total number of elapsed time in milliseconds
     *
     * @return double Total number of elapsed milliseconds
     */
    public function getDuration()
    {
        $last = $this->getLastBenchmark();
        if ($last) {
            return ($last->getEndTime() - $this->getStartTime());
        }
        return (microtime(true) - $this->getStartTime());
    }

    /**
     * Get the start of the profiler in microtime
     *
     * @return double Timestamp in microtime
     */
    public function getStartTime()
    {
        return $this->start;
    }

    /**
     * Return all measured benchmarks
     *
     * @return BenchmarkInterface[]
     */
    public function getBenchmarks()
    {
        return $this->benchmarks;
    }

    /**
     * Get a specific of the last started benchmark
     *
     * @param string $token
     * @return BenchmarkInterface|null
     */
    public function getBenchmark($token = null)
    {
        if(!$token) {
            return $this->getLastBenchmark();
        }

        if (!isset($this->benchmarks[$token])) {
            throw new UnknownBenchmarkException('Unknown benchmark: ' . $token);
        }

        return $this->benchmarks[$token];
    }

    /**
     * @return BenchmarkInterface|null
     */
    public function getLastBenchmark()
    {
        $last = array_slice($this->benchmarks, -1, 1);
        if ($last) {
            return current($last);
        }

        return new UnknownBenchmarkException('No benchmarks to return last one');
    }

    /**
     * Get the total number of benchmarks
     *
     * @return int Total number of benchmarks
     */
    public function count()
    {
        return count($this->benchmarks);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     *
     * @link http://php.net/manual/en/iterator.current.php
     * @return BenchmarkInterface Can return any type.
     */
    public function current()
    {
        return current($this->benchmarks);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     *
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        next($this->benchmarks);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     *
     * @link http://php.net/manual/en/iterator.key.php
     * @return integer scalar on success, or null on failure.
     */
    public function key()
    {
        return key($this->benchmarks);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     *
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     *       Returns true on success or false on failure.
     */
    public function valid()
    {
        $key = key($this->benchmarks);

        if ($key === false || $key === null) {
            return false;
        }

        return array_key_exists($key, $this->benchmarks);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     *
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        reset($this->benchmarks);
    }
}
