import { registerWorker } from './utils.mjs'
import { defineTrialManagerElement } from "./trial-manager.mjs";

setTimeout(() => {
  defineTrialManagerElement()
  registerWorker(
    `${window.location.origin}/service-worker.mjs`,
    `${window.location.origin}/service-worker.js`
  )
}, 2000)
