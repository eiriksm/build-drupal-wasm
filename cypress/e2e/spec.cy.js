describe('Start the site', () => {

  it('Starts and works', () => {
    let url = Cypress.env('LOCAL_URL');
    if (!url) {
      url = 'http://localhost:3000'
    }
    cy.visit(url)
    function recursivelyClickNewOrNot(runCount) {
      runCount++;
      if (runCount > 120) {
        // We waited for 2 mins. Let's call it a day.
        return
      }
      // Check if an element with the id of "new" exists.
      const $new = Cypress.$('#new')
      // If the element exists, click it.
      if ($new.length) {
        $new.click()
      } else {
        // See if there is a div.progress element.
        const $progress = Cypress.$('div.progress')
        // If there is a div.progress element, we are in the process of doing
        // it. Meaning there was never an existing one, and we are rolling.
        if (!$progress.length) {
          // If there is no div.progress element, click the button with the id of "new".
          setTimeout(recursivelyClickNewOrNot.bind(null, runCount), 1000)
        }
      }
    }
    recursivelyClickNewOrNot(0)
    cy.wait(240000)
    cy.get('body.user-logged-in').should('exist');
  })
})
