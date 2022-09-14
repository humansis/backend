<?php
declare(strict_types=1);

namespace CommonBundle\Utils\Test\Contraint;

use PHPUnit\Framework\Constraint\Constraint;

class MatchArrayFragment extends Constraint
{
    private $additionalFailureMessage = '';

    /** @var array */
    private $expected;

    public function __construct($expectedArray)
    {
        $this->expected = $expectedArray;
    }

    protected function matches($actual): bool
    {
        if (!is_array($this->expected)) {
            $this->additionalFailureMessage = 'Is not valid Array';

            return false;
        }

        if (!is_array($actual)) {
            $this->additionalFailureMessage = 'Is not valid Array';

            return false;
        }

        return $this->recursiveMatches($actual, $this->expected);
    }

    private function recursiveMatches($actual, $expected)
    {

        foreach ($expected as $key => $expectedValue) {
            if (array_key_exists($key, $actual)) {
                if (is_array($expectedValue)) {
                    return $this->recursiveMatches($actual[$key], $expectedValue);
                } elseif ('*' === $expectedValue) {
                    // ignore
                } elseif ($expectedValue !== $actual[$key]) {
                    $this->additionalFailureMessage = sprintf(
                        "attribute '%s' must contain value '%s'. '%s' given.",
                        $key,
                        $this->exporter()->export($expectedValue),
                        $this->exporter()->export($actual[$key])
                    );

                    return false;
                }
            } else {
                $this->additionalFailureMessage = sprintf("does not contain attribute '%s'.", $key);

                return false;
            }
        }

        return true;
    }

    protected function additionalFailureDescription($other): string
    {
        return $this->additionalFailureMessage;
    }

    public function toString(): string
    {
        return 'match '.$this->exporter()->export($this->expected);
    }
}
