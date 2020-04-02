<?php

namespace App\Service;

class Collection implements \Iterator
{
    private array $data;
    private int $index;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function pluck(string $name): self
    {
        if (empty($this->data) || !(is_array($this->data[0]) || is_object($this->data[0]))) {
            return new self();
        }

        if (is_array($this->data[0])) {
            $results = new self();
            foreach ($this->data as $item) {
                if (isset($item[$name])) {
                    $results->push($item[$name]);
                }
            }

            return $results;
        }

        if (is_object($this->data[0])) {
            $results = new self();
            foreach ($this->data as $item) {
                if (method_exists($item, $name)) {
                    $results->push($item->$name());
                } elseif (method_exists($item, 'get' . ucfirst($name))) {
                    $methodName = 'get' . ucfirst($name);
                    $results->push($item->$methodName());
                } elseif (property_exists($item, $name)) {
                    $results->push($item->$name);
                }
            }

            return $results;
        }

        return new self();
    }

    public function push($item): self
    {
        $this->data[] = $item;

        return $this;
    }

    public function contains($search): bool
    {
        foreach ($this->data as $item) {
            if ($item === $search) {
                return true;
            }
        }

        return false;
    }

    public function map($callback): self
    {
        if (!is_callable($callback)) {
            return $this;
        }

        $result = [];

        foreach ($this->data as $item) {
            $result[] = $callback($item);
        }

        $this->data = $result;

        return $this;
    }

    public function first()
    {
    	return count($this->data) > 0 ? $this->data[0] : null;
    }

    public function flatten(int $depth = 500): self
    {
        $result = [];

        foreach ($this->data as $item) {
            $item = $item instanceof Collection ? $item->all() : $item;

            if (!is_array($item)) {
                $result[] = $item;
            } elseif ($depth === 1) {
                $result = array_merge($result, array_values($item));
            } else {
                $result = array_merge($result, (new Collection($item))->flatten($depth - 1)->toArray());
            }
        }

        return new Collection($result);
    }

    public function all(): array
    {
        return array_values($this->data);
    }

    private function getValues($item)
    {
        return array_values($item);
    }

    public function current()
    {
        if ($this->valid()) {
            return $this->data[$this->index];
        }

        return null;
    }

    public function next(): void
    {
        $this->index++;
    }

    public function key()
    {
        return $this->index;
    }

    public function valid(): bool
    {
        return $this->index >= 0 && $this->index < count($this->data);
    }

    public function rewind(): void
    {
        $this->index = 0;
    }
}
