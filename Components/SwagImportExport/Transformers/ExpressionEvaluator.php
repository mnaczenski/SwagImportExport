<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Transformers;

interface ExpressionEvaluator
{
    /**
     * @param $expression
     * @param $variables
     * @return mixed
     */
    public function evaluate($expression, $variables);
}
