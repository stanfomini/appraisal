<template>
  <div class="slack-settings">
    <h2>Slack Integration</h2>

    <!-- If connected, show status + reset button -->
    <p v-if="isSlackConnected">Slack is connected!</p>
    <button
      v-if="isSlackConnected"
      @click="resetSlackToken"
      class="btn btn-danger"
    >
      Reset Slack Token
    </button>

    <!-- If not connected, show 'Connect' button -->
    <button
      v-else
      @click="connectSlack"
      class="btn"
    >
      Connect to Slack
    </button>
  </div>
</template>

<script setup>
import { useSlack } from '@/composables/useSlack'
import axios from 'axios'
import { route } from 'ziggy-js'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

// Pull in Slack composable
const { isSlackConnected, connectSlack } = useSlack()

// Access current tenant from Inertia props
const page = usePage()
const tenant = computed(() => page.props.tenant)

// Reset Slack token
async function resetSlackToken() {
  try {
    // Example route => route('slack.resetToken')
    // If you need a tenant param: route('slack.resetToken', { tenant: tenant.value })
    const response = await axios.post(route('slack.resetToken', { tenant: tenant.value }))
    console.log(response.data)

    // Force a reload or re-fetch to update 'isSlackConnected'
    window.location.reload()
  } catch (error) {
    console.error('Error resetting Slack token', error)
  }
}
</script>

<style scoped>
.slack-settings {
  padding: 20px;
}
.btn {
  padding: 10px 20px;
  background: #007a5a;
  color: white;
  border: none;
  border-radius: 5px;
  margin-right: 10px;
}
.btn.btn-danger {
  background-color: #c53030;
}
</style>

