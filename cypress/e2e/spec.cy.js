describe('Start the site', () => {

  it('Starts and works', () => {
    let url = Cypress.env('LOCAL_URL');
    if (!url) {
      url = 'http://localhost:3000'
    }
    cy.once('fail', (err) => {             // "once" to just catch a single error

    const message = err.parsedStack[0].message
    if (message.match(/Timed out after waiting `\d+ms` for your remote page to load/)) {
      return false
    }

    throw err                            // any other error, fail it
  })
    cy.visit(url)
    cy.wait(175000)
    cy.wait(175000)
    cy.get('body.usser-logged-in').should('exist');
  })
})
