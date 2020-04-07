<?php

namespace App\Service;

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

    public function toArray(): array
    {
        return $this->data;
    }

    public function pluck(string $name): self
    {
        if (empty($this->data)) {
            return new self();
        }

        $results = new self();
        foreach ($this->data as $item) {
            if (is_array($item)) {
                if (isset($item[$name])) {
                    $results->push($item[$name]);
                }
            } elseif (is_object($item)) {
                if (method_exists($item, $name)) {
                    $results->push($item->$name());
                } elseif (method_exists($item, 'get' . ucfirst($name))) {
                    $methodName = 'get' . ucfirst($name);
                    $results->push($item->$methodName());
                } elseif (property_exists($item, $name)) {
                    $results->push($item->$name);
                }
            }
        }

        return $results;
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

    public function isEmpty(): bool
    {
        return count($this->data) === 0;
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
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

    public function current()
    {
        return $this->iterator->current();
    }

    public function next(): void
    {
        $this->iterator->next();
    }

    public function key()
    {
        return $this->iterator->key();
    }

    public function valid(): bool
    {
        return $this->iterator->valid();
    }

    public function rewind(): void
    {
        $this->iterator->rewind();
    }
}
