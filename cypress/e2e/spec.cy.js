describe('My First Test', () => {

  it('Does not do much!', () => {
    cy.on('url:changed', () => {
      window.document.addEventListener("DOMContentLoaded", (event) => {
        var evt = window.document.createEvent('Event');  
        evt.initEvent('load', false, false);  
        window.dispatchEvent(evt);
      })
    })
    let url = Cypress.env('LOCAL_URL');
    if (!url) {
      url = 'http://localhost:3000'
    }
    cy.visit(url)
    cy.wait(300000)
    cy.get('body.user-logged-in').should('exist');
  })
})
