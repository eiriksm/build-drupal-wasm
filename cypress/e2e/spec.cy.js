describe('Start the site', () => {

  it('Starts and works', () => {
    let url = Cypress.env('LOCAL_URL');
    if (!url) {
      url = 'http://localhost:3000'
    }
    cy.visit(url)
    cy.wait(175000)
    cy.wait(175000)
    const win = cy.reload()
    cy.get('body.user-logged-in').should('exist');
  })
})
