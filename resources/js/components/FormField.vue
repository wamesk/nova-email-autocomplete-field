<template>
    <DefaultField
        :field="currentField"
        :errors="errors"
        :show-help-text="showHelpText"
        :full-width-content="fullWidthContent"
    >
        <template #field>
            <div class="input-wrapper position-relative">
                <input
                    :id="currentField.attribute"
                    type="text"
                    class="w-full form-control form-input form-control-bordered"
                    :class="errorClasses"
                    :placeholder="currentField.name"
                    v-model="value"
                    autocomplete="email.unique"
                    @keyup="keyUp"
                    @keydown="keyDown"
                    @focus="changeFocusState(true)"
                    @blur="changeFocusState(false)"
                />
                <ul
                    class="position-absolute w-full bg-white dark:bg-gray-900"
                    v-if="suggestions.length && focused"
                >
                    <li
                        class="w-full px-3 py-1.5 cursor-pointer text-gray-500 dark:text-gray-400 border-gray-100 dark:border-gray-700"
                        v-for="(suggestion, index) in suggestions"
                        :key="suggestion"
                        :class="(index === selectedIndex) ? 'bg-primary-500 text-white' : 'hover:bg-gray-100 dark:hover:bg-gray-800'"
                        @click="handleSuggestionClick(index)"
                    >
                        {{ suggestion.value }}
                    </li>
                </ul>

                <div v-if="unique.enabled" class="mt-1">
                    <div v-if="unique.status === 'loading'" class="flex flex-start">
                        <Loader width="20" class="ml-1 mr-2" />
                        {{ __('nova-email-autocomplete-field.loading') }}
                    </div>
                    <div v-else-if="unique.status === 'valid'" class="text-green-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16" class="inline-block component-heroicons-outline-check-circle component-icon component-icon-boolean" role="presentation"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        {{ __(unique.message) }}
                    </div>
                    <div v-else-if="unique.status === 'invalid'" class="text-red-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16" class="inline-block component-heroicons-outline-x-circle component-icon component-icon-boolean" role="presentation"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        {{ __(unique.message) }}
                        <a v-if="unique.url && currentField.unique_resource !== false" :href="unique.url" class="link-default component-inertia-link">{{ __('nova-email-autocomplete-field.view') }}</a>
                    </div>
                    <div v-else-if="unique.status === 'error'" class="text-red-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16" class="inline-block component-heroicons-outline-x-circle component-icon component-icon-boolean" role="presentation"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        {{ __('nova-email-autocomplete-field.error') }}
                    </div>
                </div>
            </div>
        </template>
    </DefaultField>
</template>

<script>
import {DependentFormField, HandlesValidationErrors} from 'laravel-nova'
import axios from 'axios'

export default {
    mixins: [DependentFormField, HandlesValidationErrors],

    props: ['resourceName', 'resourceId', 'field'],

    data() {
        return {
            domains: [],
            suggestions: [],
            selectedIndex: 0,
            focused: false,
            unique: {
                enabled: false,
                status: null,
            },
        };
    },

    mounted() {
        this.domains = this.currentField.domains

        if (this.currentField.unique) {
            this.unique.enabled = true
        }
    },

    methods: {
        setInitialValue() {
            this.value = this.currentField.value || ''
        },

        fill(formData) {
            console.log('formData', formData)
            formData.append(this.fieldAttribute, this.value || '')
        },

        keyUp(event) {
            this.value = this.value.trim()

            let code = event.code
            if (code === 'ArrowDown' || code === 'ArrowUp' && code === 'Space') {
                return
            }

            if (this.value === '') {
                this.suggestions = []
                return
            }

            const count = this.countAtSymbols(this.value)

            if (count >= 2) {
                this.suggestions = []
                return
            }

            if (count === 0) {
                this.getSuggestions('', false)
                return
            }

            if (count === 1) {
                const partDomain = this.value.split('@')[1]
                this.getSuggestions(partDomain, true)
            }
        },

        keyDown(event) {
            const code = event.code
            const maxIndex = this.suggestions.length - 1

            // Reset unique status
            this.unique.status = null

            if (code === 'Enter') {
                event.preventDefault()
            }

            if (code === 'ArrowUp' || code === 'ArrowDown') {
                if (maxIndex === 0) {
                    return
                }
                if (code === 'ArrowUp') {
                    if (this.selectedIndex === 0) {
                        this.selectedIndex = maxIndex
                    } else {
                        this.selectedIndex--
                    }
                } else {
                    if (this.selectedIndex === maxIndex) {
                        this.selectedIndex = 0
                    } else {
                        this.selectedIndex++
                    }
                }
            } else if (code === 'Space' || code === 'Enter') {
                this.selectSuggested(this.selectedIndex)
            }
        },

        selectSuggested(index) {
            const suggestion = this.suggestions[index];
            this.value = suggestion ? suggestion.value : this.value
        },

        countAtSymbols(string) {
            const matches = string.match(/@/g)
            return matches ? matches.length : 0
        },

        getSuggestions(string, hasAtSymbol, value = '') {
            let suggestions = []

            this.selectedIndex = 0

            if (value === '') {
                if (hasAtSymbol) {
                    value = this.value.split('@')[0]
                } else {
                    value = this.value
                }
            } else {
                value = value.split('@')[0]
            }

            for (let i = 0; i < this.domains.length; i++) {
                const domain = this.domains[i]

                if (suggestions.length === 10) {
                    break
                }

                if (domain.toLowerCase().includes(string.toLowerCase())) {
                    let suggestion = {
                        key: domain,
                        value: value + '@' + domain,
                    }
                    suggestions.push(suggestion)
                }
            }

            this.suggestions = suggestions
        },

        changeFocusState(focused) {
            if (this.unique.enabled === true && focused === false) {
                this.checkUnique()
            }

            setTimeout(() => {
                this.focused = focused
            }, 100);
        },

        handleSuggestionClick(index) {
            this.selectSuggested(index)

            const suggestion = this.suggestions[index]
            this.getSuggestions(suggestion.key, true, suggestion.value)

            this.checkUnique()
        },

        checkUnique() {
            if (this.value === '') {
                return;
            }

            this.unique.status = 'loading'

            const data = this.currentField.unique
            data.unique_resource = this.currentField.unique_resource ?? null
            data.email = this.value

            const url = window.location.pathname
            if (url.endsWith('/edit')) {
                const regex = /resources\/[^\/]+\/([^\/]+)\//;
                const match = url.match(regex);
                data.resourceId = match ? match[1] : null;
            }

            axios.post('/email-check-exists', data)
                .then(response => {
                    this.unique = response.data
                }).catch(error => {
                    this.unique.status = 'error'
                })
        }
    },
}
</script>
<style>
.input-wrapper {
    position: relative;
}

.input-wrapper ul {
    position: absolute;
    z-index: 1000;
    padding: .25rem;
    border-radius: .5rem;
    box-shadow: 0px 5px 20px 0px rgba(0,0,0,.25);
    -webkit-box-shadow: 0px 5px 20px 0px rgba(0,0,0,.25);
    -moz-box-shadow: 0px 5px 20px 0px rgba(0,0,0,.25);
}

.input-wrapper ul li {
    border-radius: .25rem;
}
</style>
