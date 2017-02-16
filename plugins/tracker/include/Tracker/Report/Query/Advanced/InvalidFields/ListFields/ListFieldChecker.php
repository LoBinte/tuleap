<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields;

use Tracker_FormElement_Field;
use Tuleap\Tracker\Report\Query\Advanced\CollectionOfListValuesExtractor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EmptyStringChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InvalidFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\MySelfIsNotSupportedForAnonymousException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\NowIsNotSupportedException;

class ListFieldChecker implements InvalidFieldChecker
{
    /**
     * @var EmptyStringChecker
     */
    private $empty_string_checker;

    /**
     * @var CollectionOfListValuesExtractor
     */
    private $values_extractor;
    /**
     * @var CollectionOfNormalizedBindLabelsExtractor
     */
    private $bind_labels_extractor;
    /**
     * @var BindValueNormalizer
     */
    private $value_normalizer;

    public function __construct(
        EmptyStringChecker $empty_string_checker,
        CollectionOfListValuesExtractor $values_extractor,
        BindValueNormalizer $value_normalizer,
        CollectionOfNormalizedBindLabelsExtractor $bind_labels_extractor
    ) {
        $this->empty_string_checker  = $empty_string_checker;
        $this->values_extractor      = $values_extractor;
        $this->bind_labels_extractor = $bind_labels_extractor;
        $this->value_normalizer      = $value_normalizer;
    }

    public function checkFieldIsValidForComparison(
        Comparison $comparison,
        Tracker_FormElement_Field $field
    ) {
        $values            = $this->values_extractor->extractCollectionOfValues($comparison->getValueWrapper(), $field);
        $normalized_labels = $this->bind_labels_extractor->extractCollectionOfNormalizedLabels($field);

        foreach ($values as $value) {
            if ($this->empty_string_checker->isEmptyStringAProblem($value)) {
                throw new ListToEmptyStringComparisonException($comparison, $field);
            }

            $normalized_value = $this->value_normalizer->normalize($value);
            if ($value !== '' && ! in_array($normalized_value, $normalized_labels)) {
                throw new ListValueDoNotExistComparisonException($field, $value);
            }
        }
    }
}
