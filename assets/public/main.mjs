import { registerWorker } from './utils.mjs'
import { defineTrialManagerElement } from "./trial-manager.mjs";
alert('hello')
setTimeout(() => {
  defineTrialManagerElement()
  setTimeout(() => {
  registerWorker(
    `${window.location.origin}/service-worker.mjs`,
    `${window.location.origin}/service-worker.js`
  )
  }, 2000)
}, 2000)
