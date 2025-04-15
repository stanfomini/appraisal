import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useSlack() {
    const page = usePage();
    const tenant = computed(() => page.props.tenant);
    const slack_bot_token = computed(() => page.props.slack_bot_token);
    const isSlackConnected = computed(() => page.props.slack_bot_token);

    const connectSlack = () => {
        window.location.href = `/slack/oauth?tenant=${tenant.value}`;
    };

    return { isSlackConnected, connectSlack };
}
