<?php

abstract class KnapsackSolver
{
    protected $items;
    protected $knapsacks;

    public function __construct(array $items, array $knapsacks)
    {
        $this->items = $items;
        $this->calculateDynamicFactor();

        $this->knapsacks = $knapsacks;
        $this->bindKnapsacks();
    }

    protected function addItem(array &$bag, array $item)
    {
        $bag['current_capacity'] += $item['weight'];
        $bag['items'][] = $item;
    }

    private function calculateDynamicFactor()
    {
        foreach ($this->items as $i => $item) {
            if ($item['fragile']) {
                $item['value'] = $item['value'] * $item['dynamic_factor'];
                $items[$i] = $item;
            }
        }
    }

    private function bindKnapsacks()
    {
        array_walk($this->knapsacks, function(&$knapsack) {
            $knapsack = [
                'current_capacity' => 0,
                'items' => [],
                'total_capacity' => 0,
                'total_value' => 0,
                'capacity' => $knapsack['capacity'],
            ];
        });
    }

    abstract public function solve(): array;
}

class GreedyApproach extends KnapsackSolver
{
    public function solve(): array
    {
        $bags = $this->knapsacks;
        $items = $this->items;
        $this->sortByValueDesc($items);

        $bagIndex = 0;
        foreach ($items as $item) {
            if ($this->isCapacityEnough($item['weight'], $bags[$bagIndex]['current_capacity'], $bags[$bagIndex]['capacity'])) {
                $this->addItem($bags[$bagIndex], $item);
            } else {
                $increment = 1;

                while (true) {
                    // Avoid array out of bounds
                    if ($increment + $bagIndex >= count($bags)) {
                        break;
                    }

                    if ($this->isCapacityEnough($item['weight'], $bags[$bagIndex + $increment]['current_capacity'], $bags[$bagIndex + $increment]['capacity'])) {
                        $this->addItem($bags[$bagIndex + $increment], $item);
                        break;
                    }

                    $increment++;
                }
            }
        }

        foreach ($bags as $i => $bag) {
            $bags[$i]['total_capacity'] = $bag['current_capacity'];
            $bags[$i]['total_value'] = array_sum(array_column($bag['items'], 'value'));
        }

        return $bags;
    }

    private function sortByValueDesc(array &$items)
    {
        usort($items, function ($a, $b) {
            return $a['value'] < $b['value'];
        });
    }

    private function isCapacityEnough(int $itemWeight, int $currentCapacity, int $maxCapacity): bool
    {
        return $itemWeight + $currentCapacity <= $maxCapacity;
    }
}

class DynamicProgrammingApproach extends KnapsackSolver
{
    public function solve(): array
    {
        $items = $this->items;
        $bag = $this->knapsacks[0];

        $matrix = [];
        for ($i = 0; $i < count($items)+1; $i++) {
            $matrix[$i] = array_fill(0, $bag['capacity']+1, 0);
        }

        for ($i = 1; $i <= count($items); $i++) {
            for ($j = 1; $j <= $bag['capacity']; $j++) {
                if ($items[$i-1]['weight'] <= $j) {
                    $previousValue = $matrix[$i-1][$j];
                    $currentValue = $items[$i-1]['value'] + $matrix[$i-1][$j-$items[$i-1]['weight']];
                    $matrix[$i][$j] = max($previousValue, $currentValue);
                } else {
                    $matrix[$i][$j] = $matrix[$i-1][$j];
                }
            }
        }

        $this->checkItem($bag, count($items), $bag['capacity'], $items, $matrix);
    
        $bag['total_capacity'] = $bag['current_capacity'];
        $bag['total_value'] = $matrix[count($items)][$bag['capacity']];

        return $bag;
    }

    private function checkItem(array &$bag, int $itemLen, int $capacity, array $items, array $matrix)
    {
        if ($itemLen <= 0 || $capacity <= 0) {
            return;
        }

        $pick = $matrix[$itemLen][$capacity];
        if ($pick != $matrix[$itemLen-1][$capacity]) {
            $this->addItem($bag, $items[$itemLen-1]);
            $this->checkItem($bag, $itemLen-1, $capacity-$items[$itemLen-1]['weight'], $items, $matrix);
        } else {
            $this->checkItem($bag, $itemLen-1, $capacity, $items, $matrix);
        }
    }
}

// Example items and knapsacks
$items = [
    ['name' => 'Item 1', 'weight' => 3, 'value' => 8, 'fragile' => true, 'dynamic_factor' => 0.7],
    ['name' => 'Item 2', 'weight' => 4, 'value' => 10, 'fragile' => false, 'dynamic_factor' => 1.0],
    ['name' => 'Item 3', 'weight' => 2, 'value' => 5, 'fragile' => false, 'dynamic_factor' => 0.9],
    ['name' => 'Item 4', 'weight' => 5, 'value' => 12, 'fragile' => true, 'dynamic_factor' => 0.6]
];

$knapsacks = [
    ['name' => 'Knapsack 1', 'capacity' => 10],
    ['name' => 'Knapsack 2', 'capacity' => 8]
];

// Create the solver instance
$solverWithGreedy = new GreedyApproach($items, $knapsacks);
$solverDynamicProgramming = new DynamicProgrammingApproach($items, $knapsacks);

// Print the solution
print_r($solverWithGreedy->solve());
print_r($solverDynamicProgramming->solve());
