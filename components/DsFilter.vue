<template>
    <v-menu v-model="show" :close-on-content-click="false" offset-x tile>
        <template #activator="{ attrs, on }">
            <span v-bind="attrs" v-on="on" class="filter-header">
                {{ name }}
                <v-badge v-if="selected.length" 
                    class="badge" 
                    :content="selected.length" 
                    offset-y="5" 
                    offset-x="9" 
                    color="orange" 
                    dark>
                    <v-icon small color="error">{{mdiFilter}}</v-icon>
                </v-badge>
                <v-icon v-else small>{{mdiFilter}}</v-icon>
            </span>
        </template>
        <v-card>
            <v-autocomplete
                v-model="selected"
                :items="filteredItems"
                :search-input.sync="searchQuery"
                :label="$t('Search')"
                multiple
                chips
                clearable
                dense
                hide-details
                class="px-2 pt-2"
                @click:clear="clearFilter"
            >
                <template v-slot:selection="{ item, index }">
                    <v-chip v-if="index < 2" small>
                        {{ item }}
                    </v-chip>
                    <span v-else-if="index === 2" class="grey--text caption">
                        (+{{ selected.length - 2 }})
                    </span>
                </template>
            </v-autocomplete>
            <v-divider class="mt-2"></v-divider>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn small text @click="clearFilter">
                    {{ $t('Clear') }}
                </v-btn>
                <v-btn small color="primary" text @click="show = false">
                    {{ $t('Close') }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-menu>
</template>

<script>
import { mdiFilter } from '@mdi/js'

export default {
    name: 'DsFilter',
    props: {
        inItems: {
            type: Array,
            default: () => [],
        },
        name: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            show: false,
            selected: [],
            mdiFilter,
            searchQuery: null,
        }
    },
    computed: {
        filteredItems() {
            if (!this.searchQuery) return this.inItems;
            
            const query = this.searchQuery.toLowerCase().trim();
            return this.inItems.filter(item => 
                String(item).toLowerCase().includes(query)
            );
        }
    },
    watch: {
        searchQuery(newVal) {
            if (!newVal) {
                this.selected = [];
                return;
            }
            const query = newVal.toLowerCase().trim();
            if (query) {
                const matchedItems = this.inItems.filter(item =>
                    String(item).toLowerCase().includes(query)
                );
                this.selected = [...new Set([...this.selected, ...matchedItems])];
            }
        },
        selected(newVal) {
            this.$emit('changed', newVal)
        },
    },
    methods: {
        clearFilter() {
            this.selected = [];
            this.searchQuery = null;
        },
    },
}
</script>

<style scoped>
.filter-header {
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.badge {
    margin-left: 4px;
}
.v-menu {
    display: inline-block;
}
</style>