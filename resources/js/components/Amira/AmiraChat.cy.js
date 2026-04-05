import AmiraChat from './AmiraChat.vue'

describe('<AmiraChat />', () => {
  it('renders', () => {
    // see: https://on.cypress.io/mounting-vue
    cy.mount(AmiraChat)
  })
})