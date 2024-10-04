describe('My First Test', () => {
  it('Does not do much!', () => {
    let url = Cypress.env('EXTERNAL_API');
    if (!url) {
      url = 'http://localhost:3000'
    }
    cy.visit(url)
    let element = cy.get('[mode="existing_session"', {timeout: 20000})
    if (element) {
      cy.on('window:confirm', (str) => {
        return true
      })
      cy.get('#new').click()
    }
    cy.get('body.user-logged-in', {timeout: 120000}).should('exist');
  })
})