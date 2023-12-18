<template>
  <DefaultField
    :field="field"
    :errors="errors"
    :show-help-text="showHelpText"
    :full-width-content="fullWidthContent"
  >
    <template #field>
        <div class="input-wrapper position-relative">
            <input
              :id="field.attribute"
              type="text"
              class="w-full form-control form-input form-input-bordered"
              :class="errorClasses"
              :placeholder="field.name"
              v-model="value"
              @keyup="keyUp"
              @keydown="keyDown"
              @focus="changeFocusState(true)"
              @blur="changeFocusState(false)"
            />
            <ul
                class="position-absolute w-full bg-white dark:bg-gray-900"
                v-if="suggestions.length && focused"
            >
<!--                <li>-->
<!--                    Showing {{ suggestedDomains.length }} of {{ domains.length }} results-->
<!--                </li>-->
<!--                bg-primary-500-->
                <li
                    class="w-full px-3 py-1.5 cursor-pointer text-white dark:text-gray-400 border-gray-100 dark:border-gray-700"
                    v-for="(suggestion, index) in suggestions"
                    :key="suggestion"
                    :class="(index === selectedIndex) ? 'bg-primary-500' : 'hover:bg-gray-100 dark:hover:bg-gray-800'"
                    @click="handleSuggestionClick(index)"
                >
                    {{ suggestion.value }}
                </li>
            </ul>
        </div>
    </template>
  </DefaultField>
</template>

<script>
import { FormField, HandlesValidationErrors } from 'laravel-nova'

export default {
    mixins: [FormField, HandlesValidationErrors],

    props: ['resourceName', 'resourceId', 'field'],

    data() {
        return {
            domains: [],
            suggestions: [],
            selectedIndex: 0,
            focused: false,
        };
    },

    mounted() {
        this.domains = this.field.domains
    },

    methods: {
        setInitialValue() {
            this.value = this.field.value || ''
        },

        fill(formData) {
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

            console.log('code', code)

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
                        this.selectedIndex --
                    }
                } else {
                    if (this.selectedIndex === maxIndex) {
                        this.selectedIndex = 0
                    } else {
                        this.selectedIndex ++
                    }
                }
            } else if (code === 'Space' || code === 'Enter') {
                this.selectSuggested(this.selectedIndex)
            }
        },

        selectSuggested(index) {
            const suggestion = this.suggestions[index];
            console.log('suggestion', suggestion)
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

                // if (i === 0) {
                //     if (this.selected !== {}) {
                //         for (let x = 0; i < this.)
                //     } else {
                //         this.selected = {
                //             index: i,
                //             domain: domain,
                //         }
                //     }
                // }

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
            console.log('this.suggestedDomains', this.suggestions)
        },

        changeFocusState(focused) {
            setTimeout(() => {
                this.focused = focused
            }, 100);
        },

        handleSuggestionClick(index) {
            this.selectSuggested(index)

            const suggestion = this.suggestions[index]
            this.getSuggestions(suggestion.key, true, suggestion.value)
        }
    },
}
</script>
<style>
.input-wrapper ul {
    position: absolute;
    z-index: 1000;
}
</style>
