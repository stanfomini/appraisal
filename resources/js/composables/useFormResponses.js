// resources/js/composables/useFormResponses.js
import { ref } from 'vue'
import { usePage, useForm, Inertia } from '@inertiajs/vue3'
import { watchFormResponseUpdates } from '@/utils/broadcast'

export function useFormResponses() {
  const page = usePage()
  const formResponses = ref(page.props.formResponses || [])

  // Example: function to accept a lead
  const acceptLead = async (responseId) => {
    // Using inertia post or an axios call
    Inertia.post(`/responses/${responseId}/accept-lead`, {}, {
      preserveScroll: true, // optional
      onSuccess: () => {
        // We could also update local state, but it might be simpler to let
        // the Inertia response update the 'formResponses' prop
      },
      onError: (errors) => {
        console.error('Accept lead error', errors)
      },
    })
  }

  // Example: send lead to Slack channel
  const broadcastToSlackChannel = async (responseId, channelId) => {
    Inertia.post(`/responses/${responseId}/slack-channel`, { channel_id: channelId }, {
      preserveScroll: true,
      onError: (errors) => console.error('Slack channel error', errors),
    })
  }

  // Start listening for broadcast events
  watchFormResponseUpdates((updatedData) => {
    // updatedData = { id, stage, appraised_by, assigned_slack_user_id, ... }
    // Find the local formResponse in the array and update it
    const idx = formResponses.value.findIndex(r => r.id === updatedData.id)
    if (idx !== -1) {
      formResponses.value[idx] = { ...formResponses.value[idx], ...updatedData }
    }
  })

  return {
    formResponses,
    acceptLead,
    broadcastToSlackChannel,
  }
}
