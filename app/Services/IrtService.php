<?php

namespace App\Services;

/**
 * Item Response Theory (IRT) Service
 * Implements 1PL (Rasch) Model for ability estimation
 */
class IrtService
{
    /**
     * Estimate student ability (theta) using 1PL IRT model
     * 
     * @param float $currentTheta Current ability estimate
     * @param array $responses Array of responses with ['difficulty' => float, 'correct' => bool]
     * @return float New theta estimate
     */
    public function estimateAbility(float $currentTheta, array $responses): float
    {
        if (empty($responses)) {
            return $currentTheta;
        }

        // Use Maximum Likelihood Estimation with Newton-Raphson method
        $theta = $currentTheta;
        $maxIterations = 20;
        $convergenceCriterion = 0.001;

        for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
            $firstDerivative = 0;
            $secondDerivative = 0;

            foreach ($responses as $response) {
                $difficulty = $response['difficulty'];
                $correct = $response['correct'] ? 1 : 0;

                // Calculate probability of correct response (1PL model)
                $probability = $this->probability($theta, $difficulty);

                // First derivative (score function)
                $firstDerivative += $correct - $probability;

                // Second derivative (information)
                $secondDerivative -= $probability * (1 - $probability);
            }

            // Newton-Raphson update
            if ($secondDerivative != 0) {
                $change = -$firstDerivative / $secondDerivative;
                $theta += $change;

                // Check for convergence
                if (abs($change) < $convergenceCriterion) {
                    break;
                }
            }
        }

        // Constrain theta to reasonable range (-3 to 3)
        return max(-3, min(3, $theta));
    }

    /**
     * Calculate probability of correct response using 1PL model
     * P(θ, b) = exp(θ - b) / (1 + exp(θ - b))
     * 
     * @param float $theta Student ability
     * @param float $difficulty Item difficulty
     * @return float Probability of correct response
     */
    public function probability(float $theta, float $difficulty): float
    {
        $exponent = $theta - $difficulty;
        return exp($exponent) / (1 + exp($exponent));
    }

    /**
     * Calculate expected score for a set of items
     * 
     * @param float $theta Student ability
     * @param array $difficulties Array of item difficulties
     * @return float Expected score
     */
    public function expectedScore(float $theta, array $difficulties): float
    {
        $expectedScore = 0;

        foreach ($difficulties as $difficulty) {
            $expectedScore += $this->probability($theta, $difficulty);
        }

        return $expectedScore;
    }

    /**
     * Calculate information for an item (1PL model)
     * I(θ, b) = P(θ, b) * (1 - P(θ, b))
     * 
     * @param float $theta Student ability
     * @param float $difficulty Item difficulty
     * @return float Information value
     */
    public function information(float $theta, float $difficulty): float
    {
        $probability = $this->probability($theta, $difficulty);
        return $probability * (1 - $probability);
    }

    /**
     * Calculate total information for a set of items
     * 
     * @param float $theta Student ability
     * @param array $difficulties Array of item difficulties
     * @return float Total information
     */
    public function totalInformation(float $theta, array $difficulties): float
    {
        $totalInfo = 0;

        foreach ($difficulties as $difficulty) {
            $totalInfo += $this->information($theta, $difficulty);
        }

        return $totalInfo;
    }

    /**
     * Calculate standard error of measurement
     * SEM = 1 / sqrt(I(θ))
     * 
     * @param float $theta Student ability
     * @param array $difficulties Array of item difficulties
     * @return float Standard error
     */
    public function standardError(float $theta, array $difficulties): float
    {
        $totalInfo = $this->totalInformation($theta, $difficulties);

        if ($totalInfo <= 0) {
            return INF;
        }

        return 1 / sqrt($totalInfo);
    }

    /**
     * Select next best item based on maximum information criterion
     * 
     * @param float $theta Current ability estimate
     * @param array $availableItems Array of items with 'id' and 'difficulty'
     * @param int $count Number of items to select
     * @return array Selected item IDs
     */
    public function selectAdaptiveItems(float $theta, array $availableItems, int $count = 20): array
    {
        // Calculate information for each item
        $itemsWithInfo = array_map(function($item) use ($theta) {
            return [
                'id' => $item['id'],
                'difficulty' => $item['difficulty'],
                'information' => $this->information($theta, $item['difficulty']),
            ];
        }, $availableItems);

        // Sort by information (descending)
        usort($itemsWithInfo, function($a, $b) {
            return $b['information'] <=> $a['information'];
        });

        // Select top items
        $selectedItems = array_slice($itemsWithInfo, 0, $count);

        return array_column($selectedItems, 'id');
    }

    /**
     * Estimate item difficulty from response data
     * 
     * @param array $responses Array of responses (correct/incorrect)
     * @return float Estimated difficulty
     */
    public function estimateItemDifficulty(array $responses): float
    {
        $totalResponses = count($responses);
        
        if ($totalResponses === 0) {
            return 0;
        }

        $correctResponses = array_sum($responses);
        $proportionCorrect = $correctResponses / $totalResponses;

        // Avoid log(0)
        $proportionCorrect = max(0.01, min(0.99, $proportionCorrect));

        // Difficulty = -log(p / (1 - p))
        return -log($proportionCorrect / (1 - $proportionCorrect));
    }

    /**
     * Get proficiency level description
     * 
     * @param float $theta Student ability
     * @return string Proficiency level
     */
    public function getProficiencyLevel(float $theta): string
    {
        if ($theta < -1) {
            return 'Beginner';
        } elseif ($theta < 0) {
            return 'Developing';
        } elseif ($theta < 1) {
            return 'Competent';
        } elseif ($theta < 2) {
            return 'Proficient';
        } else {
            return 'Advanced';
        }
    }
}