import { test, expect } from '@playwright/test';

const timeout = ms => new Promise(resolve => setTimeout(resolve, ms));

test('installs drupal and shows it', async ({ page }) => {
  test.setTimeout(300000);
  await page.goto('http://localhost:3000');
  async function recursivelyClickNewOrNot(runCount: number) {
    try {
      runCount++;
      if (runCount > 120) {
        // We waited for 2 mins. Let's call it a day.
        return
      }
      // Check if an element with the id of "new" exists.
      const $new = page.locator('#new')
      const count = await $new.count()
      // If the element exists, click it.
      if (count) {
        await $new.click()
      } else {
        // See if there is a div.progress element.
        const $progress = page.locator('div.progress')
        const progressCount = await $progress.count()
        // If there is a div.progress element, we are in the process of doing
        // it. Meaning there was never an existing one, and we are rolling.
        if (!progressCount) {
          // If there is no div.progress element, click the button with the id of "new".
          await timeout(1000);
          return await recursivelyClickNewOrNot(runCount)
        }
      }
    } catch (e) {
      return
    }
  }
  await recursivelyClickNewOrNot(0)
  const locator = page.locator('body.user-logsged-in')
  await locator.waitFor({timeout: 240000})
});