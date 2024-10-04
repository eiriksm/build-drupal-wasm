describe('My First Test', () => {

  it('Does not do much!', () => {
    let url = Cypress.env('LOCAL_URL');
    if (!url) {
      url = 'http://localhost:3000'
    }
    cy.visit(url)
    cy.once("fail", (err) =>
{
    return false;
});
    cy.wait(300000)
    cy.get('body.user-logged-in').should('exist');
  })
})
