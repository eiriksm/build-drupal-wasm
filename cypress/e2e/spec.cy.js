describe('My First Test', () => {

  it('Does not do much!', () => {
    cy.intercept('GET', '*').as('navigation')
    let url = Cypress.env('LOCAL_URL');
    if (!url) {
      url = 'http://localhost:3000'
    }
    cy.wait(300000)
    cy.get('body.user-logged-in').should('exist');
  })
})
