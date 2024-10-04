describe('My First Test', () => {

  before(async () => {
    console.log('before')
    await indexedDB.deleteDatabase('/cookies');
    indexedDB.deleteDatabase('/persist');
    indexedDB.deleteDatabase('/config');
    indexedDB.open('/cookies');
    indexedDB.open('/persist');
    indexedDB.open('/config');
    console.log('after')
  })

  it('Does not do much!', () => {
    cy.intercept('GET', '*').as('navigation')
    let url = Cypress.env('LOCAL_URL');
    if (!url) {
      url = 'http://localhost:3000'
    }
    cy.visit(url)
    cy.wait('@navigation', {timeout: 180000}).then((interception) => {
      console.log(interception.request.url)
    })
    cy.wait('@navigation', {timeout: 180000}).then((interception) => {
      console.log(interception.request.url)
    })
    cy.wait('@navigation', {timeout: 180000}).then((interception) => {
      console.log(interception.request.url)
    })
    cy.wait('@navigation', {timeout: 180000}).then((interception) => {
      console.log(interception.request.url)
    })
    cy.wait('@navigation', {timeout: 180000}).then((interception) => {
      console.log(interception.request.url)
    })
    cy.wait('@navigation', {timeout: 180000}).then((interception) => {
      console.log(interception.request.url)
    })
    cy.get('body.user-logged-in').should('exist');
  })
})