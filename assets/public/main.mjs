import { registerWorker } from './utils.mjs'
import { defineTrialManagerElement } from "./trial-manager.mjs";

setTimeout(() => {
  defineTrialManagerElement()
}, 2000)
  registerWorker(
    `${window.location.origin}/service-worker.mjs`,
    `${window.location.origin}/service-worker.js`
  )

