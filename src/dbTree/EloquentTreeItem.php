<?php

namespace Igaster\LaravelCities\dbTree;

use Igaster\LaravelCities\Geo;
use Illuminate\Database\Eloquent\Model as Eloquent;

class EloquentTreeItem extends Eloquent
{
    // Properties:
    // id, parent_id, depth, left, right

    private static $items = [];

    protected $parent = null;
    protected $children = [];

    public static function print($msg, $output)
    {
        if ($output) {
            $output->writeln('<info>- ' . $msg . '</info>');
        }
    }

    public static function rebuildTree($output = null, bool $printTree = false)
    {
        // Create associative array of all elements
        self::print('Create associative array', $output);
        foreach (self::all() as $item) {
            self::$items[$item->id] = $item;
        };

        // Fill parent/children attributes
        self::print('Create parent/children relations', $output);
        foreach (self::$items as $item) {
            if ($item->parent_id) {
                $item->parent = self::getItem($item->parent_id);
                $item->parent->addChild($item);
            }
        }

        // Build Tree for each Country (root) item
        self::print('Build Tree', $output);
        $count = 1;
        foreach (self::$items as $item) {
            if ($item->level == GEO::LEVEL_COUNTRY) {
				$count = self::buildTree($item, $count);
				if ($printTree) {
					$item->printTree();
				}
            }
        }

        // Save in DB
        self::print('Save in DB', $output);
        foreach (self::$items as $item) {
            $item->save();
        }
    }

    // Get item by id
    private static function getItem($id)
    {
        if (! isset(self::$items[$id])) {
            throw new \Exception("Item $id not found");
        }
        return self::$items[$id];
    }

    // Add $item as a child
    private function addChild($item)
    {
        $this->children[] = $item;
    }

    private static function buildTree($item, $count = 1, $depth = 0)
    {
        $item->left = $count++;
        $item->depth = $depth;
        foreach ($item->children as $child) {
            $count = $item->buildTree($child, $count, $depth + 1);
        }
        $item->right = $count++;
        return $count;
    }

    public function printTree()
    {
        $levelStr = str_repeat('-', $this->depth);
        echo(sprintf("%s %s [%d,%d]\n", $levelStr, $this->name, $this->left, $this->right));
        foreach ($this->children as $child) {
            $child->printTree();
        }
    }
}
