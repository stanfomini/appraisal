// resources/js/utils/broadcast.js
import Echo from 'laravel-echo'
import { onMounted } from 'vue'

window.Pusher = require('pusher-js') // or use the pusher-js npm package

const echo = new Echo({
  broadcaster: 'pusher',
  key: process.env.MIX_PUSHER_APP_KEY,   // or import from .env
  cluster: process.env.MIX_PUSHER_APP_CLUSTER,
  forceTLS: true,
})

let callbackRegistered = false

/**
 * Watch for FormResponseUpdated events in real-time.
 * @param {Function} callback - Called with updated formResponse data
 */
export function watchFormResponseUpdates(callback) {
  // We only want to bind once
  if (!callbackRegistered) {
    echo.channel('form-responses')
      .listen('.App\\Events\\FormResponseUpdated', (eventData) => {
        // eventData contains the broadcastWith() output
        callback(eventData)
      })
    callbackRegistered = true
  }
}
