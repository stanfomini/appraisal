<template>
  <div class="modal" @click.self="close" v-if="show">
    <div class="modal-content">
      <!-- Modal Header -->
      <div class="modal-header">
        <h3>{{ response.year }} {{ response.make }} {{ response.model }}</h3>

        <!-- Actions in top-right -->
        <div class="modal-actions">
          <!-- Local (appraised_by) user assignment -->
          <button
            v-if="!assignedUserId"
            class="btn-assign"
            @click="assignMyself"
          >
            Assign Myself
          </button>
          <div v-else class="assigned-user">
            <strong>Assigned to:</strong> {{ assignedUserName }}
          </div>

          <!-- Slack Channel Dropdown + Post Button -->
          <div class="slack-dropdown">
            <label>Post to Channel:</label>
            <select v-model="selectedChannel" @change="onChannelChange">
              <option value="" disabled>Select Channel</option>
              <option
                v-for="ch in slackChannels"
                :key="ch.id"
                :value="ch.id"
              >
                {{ ch.name }}
              </option>
            </select>
            <button
              @click="postToChannel"
              :disabled="!selectedChannel"
            >
              Post
            </button>
          </div>

          <!-- Slack User Dropdown OR Assigned User -->
          <div v-if="!assignedSlackUserId" class="slack-dropdown">
            <label>DM User:</label>
            <select v-model="selectedUser">
              <option value="" disabled>Select User</option>
              <option
                v-for="u in slackUsers"
                :key="u.id"
                :value="u.id"
              >
                {{ u.name }}
              </option>
            </select>
            <button @click="dmSlackUser" :disabled="!selectedUser">
              DM
            </button>
          </div>
          <div v-else class="assigned-user">
            <strong>Assigned to:</strong> {{ assignedSlackUsername }}
          </div>
        </div>
      </div>

      <!-- Lead Details -->
      <div class="modal-body">
        <p><strong>VIN:</strong> {{ response.vin }}</p>
        <p><strong>Owner:</strong> {{ response.full_name }}</p>
        <p><strong>Email:</strong> {{ response.email }}</p>
        <p><strong>Phone:</strong> {{ response.phone_number }}</p>

        <!-- Photos -->
        <div class="modal-photos">
          <img
            v-for="(photo, index) in response.photos"
            :key="index"
            :src="'/storage/' + photo"
            alt="Vehicle Photo"
            class="modal-photo"
          />
        </div>

        <!-- Appraisal (Manager Only) -->
        <div v-if="isManager" class="appraisal-section">
          <label><strong>Appraisal Value:</strong></label>
          <input
            type="number"
            v-model="appraisalValue"
            @input="broadcastAppraisalInput"
            placeholder="e.g. 25000"
            class="form-input"
          />
          <div v-if="typingUserName" class="typing-user">
            <span class="typing-indicator"></span>
            <small>Typing: {{ typingUserName }}</small>
          </div>
          <button @click="submitAppraisal">Appraise</button>
        </div>

        <!-- Certificate Button: Relies on the response prop -->
        <div v-if="response.certificate">
          <a :href="certificateUrl" target="_blank" class="download-link">
            Certificate
          </a>
        </div>
      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button @click="close">Close</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import { usePage, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import debounce from 'lodash/debounce';

// PROPS
const props = defineProps({
  show: { type: Boolean, default: false },
  response: { type: Object, required: true },
});

// EVENTS
const emits = defineEmits(['close', 'updated']);

// REACTIVE VARIABLES
const slackChannels = ref([]);
const selectedChannel = ref('');
const slackUsers = ref([]);
const selectedUser = ref('');
const assignedSlackUserId = ref('');
const assignedSlackUsername = ref('');
const assignedUserId = ref(null);
const assignedUserName = ref('');
const appraisalValue = ref('');
const typingUserName = ref('');
const hasCertificate = ref(!!props.response.certificate);
const certificateUrl = ref(''); // Kept as ref from working code

// TENANT / AUTH
const page = usePage();
const tenant = computed(() => page.props.tenant);
const isManager = computed(() => page.props.auth?.isManager);

// CERTIFICATE URL (Restored as computed)
const certificateUrlComputed = computed(() => {
  if (!props.response.certificate?.id) return '#';
  return route('certificates.show', {
    tenant: tenant.value,
    certificateId: props.response.certificate.id,
  });
});

// WATCH MODAL VISIBILITY
watch(
  () => props.show,
  async (newShow) => {
    if (newShow) {
      await initializeModal();
    }
  },
  { immediate: true }
);

// INITIALIZE MODAL
async function initializeModal() {
  try {
    const channelsRes = await axios.get('/slack/channels', {
      params: { tenant: tenant.value },
    });
    slackChannels.value = channelsRes.data.channels || [];
    await fetchFreshData();
    setupEchoListener();
  } catch (err) {
    console.error('Error initializing modal:', err);
  }
}

// FETCH FRESH DATA
async function fetchFreshData() {
  try {
    const response = await axios.get(
      route('responses.show', {
        tenant: tenant.value,
        id: props.response.id,
      })
    );
    const freshData = response.data;
    emits('updated', freshData);
    assignedSlackUserId.value = freshData.assigned_slack_user_id || '';
    assignedSlackUsername.value = freshData.slack_username || '';
    assignedUserId.value = freshData.appraised_by || null;
    assignedUserName.value = freshData.appraised_by
      ? (page.props.auth?.user?.id === freshData.appraised_by
          ? page.props.auth.user.name
          : freshData.assigned_user_name || 'Unknown')
      : '';
    appraisalValue.value = freshData.appraisal_value || '';
    selectedChannel.value = freshData.assigned_slack_channel || '';
    hasCertificate.value = !!freshData.certificate;
    certificateUrl.value = certificateUrlComputed.value; // Update ref with computed value
    if (freshData.assigned_slack_channel) {
      await fetchChannelMembers();
    }
    return freshData; // Changed to return freshData instead of response.data
  } catch (err) {
    console.error('Error fetching fresh data:', err);
  }
}

// FETCH CHANNEL MEMBERS
async function fetchChannelMembers() {
  if (!selectedChannel.value) {
    slackUsers.value = [];
    return;
  }
  try {
    const resp = await axios.get('/slack/channel-members', {
      params: {
        tenant: tenant.value,
        channel: selectedChannel.value,
      },
    });
    slackUsers.value = resp.data.members || [];
    if (assignedSlackUserId.value) {
      const matched = slackUsers.value.find(
        (u) => u.id === assignedSlackUserId.value
      );
      if (matched?.name) {
        assignedSlackUsername.value = matched.name;
      }
    }
  } catch (err) {
    console.error('Error fetching channel members:', err);
    slackUsers.value = [];
  }
}

// HANDLE CHANNEL CHANGE
async function onChannelChange() {
  if (!selectedChannel.value) {
    slackUsers.value = [];
    return;
  }
  await fetchChannelMembers();
}

// BROADCAST APPRAISAL INPUT
const broadcastAppraisalInput = debounce(async () => {
  try {
    await axios.post(
      route('responses.broadcastAppraisal', { tenant: tenant.value }),
      {
        id: props.response.id,
        appraisal_value: appraisalValue.value,
      }
    );
  } catch (err) {
    console.error('Error broadcasting appraisal:', err);
  }
}, 500);

// CLOSE MODAL
function close() {
  emits('close');
}

// ASSIGN MYSELF
function assignMyself() {
  router.post(
    route('responses.acceptLead', { tenant: tenant.value, id: props.response.id }),
    {},
    {
      preserveScroll: true,
      onSuccess: async () => {
        assignedUserId.value = page.props.auth.user.id;
        assignedUserName.value = page.props.auth.user.name;
        await fetchFreshData();
      },
      onError: (err) => console.error('Assign myself error:', err),
    }
  );
}

// POST TO SLACK CHANNEL
function postToChannel() {
  if (!selectedChannel.value) return;
  router.post(
    `/${tenant.value}/responses/${props.response.id}/slack-channel`,
    { channel_id: selectedChannel.value },
    {
      preserveScroll: true,
      onSuccess: () => console.log('Posted to Slack channel'),
      onError: (err) => console.error('Post to channel error:', err),
    }
  );
}

// DM SLACK USER
function dmSlackUser() {
  if (!selectedUser.value) return;
  const selectedUserId = selectedUser.value;
  const selectedUserName =
    slackUsers.value.find((u) => u.id === selectedUserId)?.name || 'Unknown';
  router.post(
    `/${tenant.value}/responses/${props.response.id}/dm-user`,
    { slack_user_id: selectedUserId },
    {
      preserveScroll: true,
      onSuccess: () => {
        assignedSlackUserId.value = selectedUserId;
        assignedSlackUsername.value = selectedUserName;
        selectedUser.value = '';
      },
      onError: (err) => console.error('DM Slack user error:', err),
    }
  );
}

// SUBMIT APPRAISAL (Updated to use axios.post)
async function submitAppraisal() {
  if (!props.response?.id || !appraisalValue.value) return;
  try {
    const response = await axios.post(
      route('responses.appraise', { tenant: tenant.value }),
      {
        id: props.response.id,
        appraisal_value: appraisalValue.value,
      }
    );
    const updatedResponse = response.data.formResponse;
    if (updatedResponse) {
      hasCertificate.value = !!updatedResponse.certificate;
      certificateUrl.value = certificateUrlComputed.value; // Update certificate URL
      emits('updated', updatedResponse);
    }
  } catch (err) {
    console.error('Appraisal submission error:', err);
  }
  // Preserve original router.post logic as fallback or additional action if needed
  await router.post(
    route('responses.appraise', { tenant: tenant.value }),
    {
      id: props.response.id,
      appraisal_value: appraisalValue.value,
    },
    {
      preserveScroll: true,
      onSuccess: async () => {
        console.log('Appraisal submitted and certificate created');
        hasCertificate.value = true;
        const freshData = await fetchFreshData();
        if (freshData) {
          emits('updated', {
            ...props.response,
            appraisal_value: appraisalValue.value,
            certificate: freshData.certificate || { id: props.response.id },
            ...freshData
          });
        }
      },
      onError: (err) => console.error('Appraisal submission error:', err),
    }
  );
}

// REAL-TIME UPDATES WITH ECHO (Enhanced to emit event data)
function setupEchoListener() {
  window.Echo.channel(`leads.${props.response.id}`).listen('LeadAssigned', (event) => {
    assignedUserId.value = event.appraised_by || null;
    assignedUserName.value = event.appraised_by
      ? (event.user_name || 'Unknown User')
      : '';
    assignedSlackUserId.value = event.assigned_slack_user_id || '';
    assignedSlackUsername.value = event.slack_username || '';
    selectedChannel.value = event.assigned_slack_channel || '';
    hasCertificate.value = !!event.certificate;
    certificateUrl.value = certificateUrlComputed.value; // Update certificate URL
    appraisalValue.value =
      event.typing_user_id !== page.props.auth.user.id
        ? event.appraisal_value || ''
        : appraisalValue.value;
    typingUserName.value = event.typing_user_id
      ? event.typing_user_name || 'Unknown User'
      : '';
    if (event.assigned_slack_channel) {
      fetchChannelMembers();
    }
    // Emit the entire event data to the parent
    emits('updated', event);
  });
}
</script>

<style scoped>
.modal {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 2000;
}
.modal-content {
  background: #fff;
  width: 90%;
  max-width: 700px;
  padding: 1rem;
  border-radius: 8px;
  max-height: 90vh;
  overflow-y: auto;
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #eee;
}
.modal-header h3 {
  margin: 0;
  flex-grow: 1;
}
.modal-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
}
.slack-dropdown {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}
.slack-dropdown label {
  white-space: nowrap;
  font-size: 0.9em;
  color: #4b5563;
}
.slack-dropdown select {
  max-width: 150px;
  padding: 0.3rem 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 0.9em;
}
.slack-dropdown button {
  padding: 0.3rem 0.6rem;
  font-size: 0.9em;
  background-color: #4f46e5;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}
.slack-dropdown button:disabled {
  background-color: #a5b4fc;
  cursor: not-allowed;
}
.slack-dropdown button:hover:not(:disabled) {
  background-color: #4338ca;
}
.btn-assign {
  background: #10b981;
  color: #fff;
  padding: 0.5rem 0.75rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  white-space: nowrap;
  font-weight: 500;
}
.btn-assign:hover {
  background: #059669;
}
.modal-body {
  margin: 1rem 0;
  padding: 0 0.5rem;
}
.modal-body p {
  margin: 0.6rem 0;
  line-height: 1.5;
  color: #374151;
}
.modal-body p strong {
  color: #1f2937;
  margin-right: 0.5em;
}
.modal-footer {
  display: flex;
  justify-content: flex-end;
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px solid #eee;
}
.modal-footer button {
  background: #6b7280;
  color: white;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
}
.modal-footer button:hover {
  background: #4b5563;
}
.modal-photos {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 0.75rem;
  margin: 1.5rem 0;
}
.modal-photo {
  width: 100%;
  height: 90px;
  object-fit: cover;
  border-radius: 4px;
  border: 1px solid #ddd;
  display: block;
}
.appraisal-section {
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px solid #eee;
}
.appraisal-section label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #1f2937;
}
.appraisal-section > div:not(.typing-user) {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.appraisal-section input.form-input {
  flex-grow: 1;
  padding: 0.5rem;
  border: 1px solid #ccc;
  border-radius: 4px;
}
.appraisal-section button {
  padding: 0.55rem 1rem;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  white-space: nowrap;
}
.appraisal-section button:hover {
  background: #2563eb;
}
.assigned-user {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 14px;
  background-color: #e5e7eb;
  padding: 0.3rem 0.7rem;
  border-radius: 12px;
  white-space: nowrap;
  color: #374151;
}
.assigned-user strong {
  font-weight: 500;
}
.typing-user {
  margin-top: 0.5rem;
  font-size: 12px;
  color: #666;
  display: flex;
  align-items: center;
  gap: 0.35rem;
  height: 16px;
  font-style: italic;
}
.typing-indicator {
  width: 8px;
  height: 8px;
  background-color: #10b981;
  border-radius: 50%;
  animation: typing-blink 1.2s infinite ease-in-out;
}
@keyframes typing-blink {
  0%, 100% { opacity: 0.2; }
  50% { opacity: 1; }
}
.download-link {
  display: inline-block;
  margin: 1.5rem 0 0.5rem;
  padding: 0.6rem 1.2rem;
  background: #1f2937;
  color: #fff;
  border-radius: 4px;
  text-decoration: none;
  font-weight: 500;
  transition: background-color 0.2s ease;
}
.download-link:hover {
  background: #111827;
}
button {
  cursor: pointer;
}
button:disabled {
  cursor: not-allowed;
  opacity: 0.7;
}
</style>