import { test, expect } from '@playwright/test';

test('installs drupal and shows it', async ({ page }) => {
  test.setTimeout(300000);
  await page.goto('http://localhost:3000');
  // async function recursivelyClickNewOrNot(runCount: number) {
  //   runCount++;
  //   if (runCount > 120) {
  //     // We waited for 2 mins. Let's call it a day.
  //     return
  //   }
  //   // Check if an element with the id of "new" exists.
  //   const $new = page.locator('#new')
  //   const count = await $new.count()
  //   // If the element exists, click it.
  //   if (count) {
  //     await $new.click()
  //   } else {
  //     // See if there is a div.progress element.
  //     const $progress = page.locator('div.progress')
  //     const progressCount = await $progress.count()
  //     // If there is a div.progress element, we are in the process of doing
  //     // it. Meaning there was never an existing one, and we are rolling.
  //     if (!progressCount) {
  //       // If there is no div.progress element, click the button with the id of "new".
  //       await new Promise(r => setTimeout(r, 1000));
  //       return recursivelyClickNewOrNot(runCount)
  //     }
  //   }
  // }
  // await recursivelyClickNewOrNot(0)
  const locator = page.locator('body.user-logged-in')
  await locator.waitFor({timeout: 240000})
});