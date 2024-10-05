describe('My First Test', () => {

  it('Does not do much!', () => {
    let url = Cypress.env('LOCAL_URL');
    if (!url) {
      url = 'http://localhost:3000'
    }
    cy.visit(url)
    cy.wait(300000)
    cy.window().then((win) => {
      var evt = document.createEvent('Event');  
evt.initEvent('load', false, false);  
window.dispatchEvent(evt);
    })
    cy.get('body.user-logged-in').should('exist');
  })
})
