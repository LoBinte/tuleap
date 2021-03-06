<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Workflow\Transition\Update;

use Tracker_FormElementFactory;
use Transition_PostAction_CIBuildDao;
use Transition_PostAction_CIBuildFactory;
use Transition_PostAction_Field_DateDao;
use Transition_PostAction_Field_FloatDao;
use Transition_PostAction_Field_IntDao;
use Transition_PostAction_FieldFactory;
use TransitionFactory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Tracker\Workflow\PostAction\PostActionsRetriever;
use Tuleap\Tracker\Workflow\PostAction\ReadOnly\ReadOnlyDao;
use Tuleap\Tracker\Workflow\PostAction\ReadOnly\ReadOnlyFieldsRetriever;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\CIBuildRepository;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\CIBuildUpdater;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\CIBuildValidator;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\PostActionFieldIdValidator;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\PostActionIdValidator;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\PostActionsMapper;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetDateValueRepository;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetDateValueUpdater;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetDateValueValidator;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetFloatValueRepository;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetFloatValueUpdater;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetFloatValueValidator;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetIntValueRepository;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetIntValueUpdater;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetIntValueValidator;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollectionUpdater;
use Tuleap\Tracker\Workflow\Transition\Condition\ConditionsUpdater;
use Workflow_Transition_ConditionFactory;

class TransitionReplicatorBuilder
{
    public static function build() : TransitionReplicator
    {
        $ids_validator        = new PostActionIdValidator();
        $field_ids_validator  = new PostActionFieldIdValidator();
        $form_element_factory = Tracker_FormElementFactory::instance();
        $transaction_executor = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());

        return new TransitionReplicator(
            Workflow_Transition_ConditionFactory::build(),
            new ConditionsUpdater(
                TransitionFactory::instance(),
                Workflow_Transition_ConditionFactory::build()
            ),
            new PostActionsRetriever(
                new Transition_PostAction_CIBuildFactory(new Transition_PostAction_CIBuildDao()),
                new Transition_PostAction_FieldFactory(
                    $form_element_factory,
                    new Transition_PostAction_Field_DateDao(),
                    new Transition_PostAction_Field_IntDao(),
                    new Transition_PostAction_Field_FloatDao()
                ),
                new ReadOnlyFieldsRetriever(new ReadOnlyDao())
            ),
            new PostActionCollectionUpdater(
                new CIBuildUpdater(
                    new CIBuildRepository(
                        new Transition_PostAction_CIBuildDao()
                    ),
                    new CIBuildValidator($ids_validator)
                ),
                new SetDateValueUpdater(
                    new SetDateValueRepository(
                        new Transition_PostAction_Field_DateDao(),
                        $transaction_executor
                    ),
                    new SetDateValueValidator($ids_validator, $field_ids_validator, $form_element_factory)
                ),
                new SetIntValueUpdater(
                    new SetintValueRepository(
                        new Transition_PostAction_Field_IntDao(),
                        $transaction_executor
                    ),
                    new SetIntValueValidator($ids_validator, $field_ids_validator, $form_element_factory)
                ),
                new SetFloatValueUpdater(
                    new SetFloatValueRepository(
                        new Transition_PostAction_Field_FloatDao(),
                        $transaction_executor
                    ),
                    new SetFloatValueValidator($ids_validator, $field_ids_validator, $form_element_factory)
                )
            ),
            new PostActionsMapper()
        );
    }
}
