import IndexField from './components/IndexField'
import DetailField from './components/DetailField'
import FormField from './components/FormField'

Nova.booting((app, store) => {
  app.component('index-nova-email-autocomplete-field', IndexField)
  app.component('detail-nova-email-autocomplete-field', DetailField)
  app.component('form-nova-email-autocomplete-field', FormField)
})
