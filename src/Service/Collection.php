<?php

namespace App\Service;

use App\Exception\AttributeNotFoundException;

class Collection implements \Iterator
{
    private array $data;
    private \ArrayIterator $iterator;

    public function __construct(array $data = [])
    {
        $this->data = $data;
        // Prepare iterator
        $this->iterator = new \ArrayIterator($this->data);
    }

    public function all(): array
    {
        return array_values($this->data);
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

    public function current()
    {
        return $this->iterator->current();
    }

    public function each($callback): self
    {
        if (!is_callable($callback)) {
            return $this;
        }

        foreach ($this->data as $datum) {
            if ($callback($datum) === false) {
                break;
            }
        }

        return $this;
    }

    public function filter($callback): self
    {
        if (!is_callable($callback)) {
            return $this;
        }

        $accepted = new self();

        foreach ($this->data as $datum) {
            if ($callback($datum) === true) {
                $accepted->push($datum);
            }
        }

        return $accepted;
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
                $result = array_merge($result, (new self($item))->flatten($depth - 1)->toArray());
            }
        }

        return new self($result);
    }

    public function get($key, $defaultValue = null)
    {
        return $this->has($key) ? $this->data[$key] : $defaultValue;
    }

    public function has($key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function isEmpty(): bool
    {
        return count($this->data) === 0;
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function key()
    {
        return $this->iterator->key();
    }

    public function keyBy($callback): self
    {
        $results = [];
        foreach ($this->data as $item) {
            $results[] = (is_callable($callback)) ? $callback($item) : $this->getValueByAccessor($item, $callback);
        }

        return new self(array_combine($results, $this->data));
    }

    public function keys(): self
    {
        return new self(array_keys($this->data));
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

        return new self($result);
    }

    public function merge(self $collection): self
    {
        return new self(array_merge($this->data, $collection->toArray()));
    }

    public function mergeRecursive(self $collection): self
    {
        return new self(array_merge_recursive($this->data, $collection->toArray()));
    }

    public function next(): void
    {
        $this->iterator->next();
    }

    public function pluck(string $name): self
    {
        if (empty($this->data)) {
            return new self();
        }

        $results = new self();
        foreach ($this->data as $item) {
            $results->push($this->getValueByAccessor($item, $name));
        }

        return $results;
    }

    public function push($item): self
    {
        $this->data[] = $item;

        return $this;
    }

    public function rewind(): void
    {
        $this->iterator->rewind();
    }

    public function toArray(): array
    {
        return array_map(function ($value) {
            return $value instanceof self ? $value->toArray() : $value;
        }, $this->data);
    }

    public function unique(): self
    {
        return new self(array_unique($this->data));
    }

    public function valid(): bool
    {
        return $this->iterator->valid();
    }

    private function getValueByAccessor($item, $name)
    {
        if (is_array($item)) {
            if (isset($item[$name])) {
                return $item[$name];
            }
        } elseif (is_object($item)) {
            if (method_exists($item, $name)) {
                return $item->$name();
            } elseif (method_exists($item, 'get' . ucfirst($name))) {
                $methodName = 'get' . ucfirst($name);
                return $item->$methodName();
            } elseif (property_exists($item, $name)) {
                return $item->$name;
            }
        }

        throw new AttributeNotFoundException(sprintf('Attribute %s could not be found', $name));
    }
}
