<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="tlp-property">
        <label v-bind:for="metadata_label" class="tlp-label">
            {{ metadata.name }}
        </label>
        <p v-bind:id="metadata_label">
            <template v-if="metadata.type === METADATA_LIST_TYPE && ! is_list_empty">
                <ul v-if="metadata.list_value.length > 1">
                    <li v-for="value in metadata.list_value" v-bind:key="value.id">
                        {{ value.name }}
                    </li>
                </ul>
                <template v-else>
                    {{ metadata.list_value[0].name }}
                </template>
            </template>
            <template v-else-if="metadata.type === METADATA_DATE_TYPE && is_date_valid">
                <div class="tlp-tooltip tlp-tooltip-left" v-bind:data-tlp-tooltip="getFormattedDate(metadata.value)">
                    {{ getFormattedDateForDisplay(metadata.value) }}
                </div>
            </template>
            <span class="document-quick-look-property-empty" v-else-if="! has_metadata_a_value" v-translate>
                Empty
            </span>
            <template v-else>
                {{ metadata.value }}
            </template>
        </p>
    </div>
</template>
<script>
import { mapState } from "vuex";
import {
    formatDateUsingPreferredUserFormat,
    isDateValid,
    getElapsedTimeFromNow
} from "../../../helpers/date-formatter.js";

export default {
    name: "QuickLookDocumentAdditionalMetadataList",
    props: {
        metadata: Object
    },
    data() {
        return {
            METADATA_LIST_TYPE: "list",
            METADATA_DATE_TYPE: "date"
        };
    },
    computed: {
        ...mapState(["date_time_format"]),
        metadata_label() {
            const metadata_name_kebab_case = this.metadata.name.toLowerCase().replace(/\s/, "-");

            return `document-${metadata_name_kebab_case}`;
        },
        is_list_empty() {
            return !this.metadata.list_value || !this.metadata.list_value.length;
        },
        is_date_valid() {
            return isDateValid(this.metadata.value);
        },
        has_metadata_a_value() {
            if (this.metadata.type === this.METADATA_LIST_TYPE) {
                return !this.is_list_empty;
            }

            if (this.metadata.type === this.METADATA_DATE_TYPE) {
                return this.is_date_valid;
            }

            return this.metadata.value;
        }
    },
    methods: {
        getFormattedDate(date) {
            return formatDateUsingPreferredUserFormat(date, this.date_time_format);
        },
        getFormattedDateForDisplay(date) {
            return getElapsedTimeFromNow(date);
        }
    }
};
</script>
