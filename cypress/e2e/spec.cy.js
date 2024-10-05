describe('My First Test', () => {

  it('Does not do much!', () => {
    let url = Cypress.env('LOCAL_URL');
    if (!url) {
      url = 'http://localhost:3000'
    }
    cy.visit(url)
    cy.window().then((win) => {
      var evt = win.document.createEvent('Event');  
      evt.initEvent('load', false, false);  
      win.dispatchEvent(evt);
      win.dispatchEvent(new Event('load'));
    })
    cy.wait(180000)
    cy.window().then((win) => {
      var evt = win.document.createEvent('Event');  
      evt.initEvent('load', false, false);  
      win.dispatchEvent(evt);
      win.dispatchEvent(new Event('load'));
    })
    cy.get('body.user-logged-in').should('exist');
  })
})
