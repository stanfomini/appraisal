<!-- resources/js/components/SellYourCarForm.vue -->
<template>
  <div class="survey-container">
    <SurveyComponent :survey="survey" />
    <transition name="fade">
      <div v-if="isLoading" class="spinner-overlay">
        <div class="spinner">
          <span class="spinner-icon"></span>
          Validating VIN...
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { Model } from 'survey-core';
import { SurveyComponent } from 'survey-vue3-ui';
import 'survey-core/survey-core.min.css';

const emit = defineEmits(['submitted']);

const themeJson = {
  themeName: 'default',
  colorPalette: 'light',
  isPanelless: false,
  backgroundImage: '',
  backgroundOpacity: 1,
  backgroundImageAttachment: 'scroll',
  backgroundImageFit: 'cover',
  cssVariables: {
    '--sjs-corner-radius': '4px',
    '--sjs-base-unit': '8px',
    '--sjs-shadow-small': '0px 1px 2px 0px rgba(0, 0, 0, 0.15)',
    '--sjs-shadow-inner': 'inset 0px 1px 2px 0px rgba(0, 0, 0, 0.15)',
    '--sjs-border-default': 'rgba(0, 0, 0, 0.16)',
    '--sjs-border-light': 'rgba(0, 0, 0, 0.09)',
    '--sjs-general-backcolor': 'rgba(255, 255, 255, 1)',
    '--sjs-general-backcolor-dark': 'rgba(248, 248, 248, 1)',
    '--sjs-general-backcolor-dim-light': 'rgba(249, 249, 249, 1)',
    '--sjs-general-backcolor-dim-dark': 'rgba(243, 243, 243, 1)',
    '--sjs-general-forecolor': 'rgba(0, 0, 0, 0.91)',
    '--sjs-general-forecolor-light': 'rgba(0, 0, 0, 0.45)',
    '--sjs-general-dim-forecolor': 'rgba(0, 0, 0, 0.91)',
    '--sjs-general-dim-forecolor-light': 'rgba(0, 0, 0, 0.45)',
    '--sjs-secondary-backcolor': 'rgba(255, 152, 20, 1)',
    '--sjs-secondary-backcolor-light': 'rgba(255, 152, 20, 0.1)',
    '--sjs-secondary-backcolor-semi-light': 'rgba(255, 152, 20, 0.25)',
    '--sjs-secondary-forecolor': 'rgba(255, 255, 255, 1)',
    '--sjs-secondary-forecolor-light': 'rgba(255, 255, 255, 0.25)',
    '--sjs-shadow-small-reset': '0px 0px 0px 0px rgba(0, 0, 0, 0.15)',
    '--sjs-shadow-medium': '0px 2px 6px 0px rgba(0, 0, 0, 0.1)',
    '--sjs-shadow-large': '0px 8px 16px 0px rgba(0, 0, 0, 0.1)',
    '--sjs-shadow-inner-reset': 'inset 0px 0px 0px 0px rgba(0, 0, 0, 0.15)',
    '--sjs-border-inside': 'rgba(0, 0, 0, 0.16)',
    '--sjs-special-red-forecolor': 'rgba(255, 255, 255, 1)',
    '--sjs-special-green': 'rgba(25, 179, 148, 1)',
    '--sjs-special-green-light': 'rgba(25, 179, 148, 0.1)',
    '--sjs-special-green-forecolor': 'rgba(255, 255, 255, 1)',
    '--sjs-special-blue': 'rgba(67, 127, 217, 1)',
    '--sjs-special-blue-light': 'rgba(67, 127, 217, 0.1)',
    '--sjs-special-blue-forecolor': 'rgba(255, 255, 255, 1)',
    '--sjs-special-yellow': 'rgba(255, 152, 20, 1)',
    '--sjs-special-yellow-light': 'rgba(255, 152, 20, 0.1)',
    '--sjs-special-yellow-forecolor': 'rgba(255, 255, 255, 1)',
    '--sjs-article-font-xx-large-textDecoration': 'none',
    '--sjs-article-font-xx-large-fontWeight': '700',
    '--sjs-article-font-xx-large-fontStyle': 'normal',
    '--sjs-article-font-xx-large-fontStretch': 'normal',
    '--sjs-article-font-xx-large-letterSpacing': '0',
    '--sjs-article-font-xx-large-lineHeight': '64px',
    '--sjs-article-font-xx-large-paragraphIndent': '0px',
    '--sjs-article-font-xx-large-textCase': 'none',
    '--sjs-article-font-x-large-textDecoration': 'none',
    '--sjs-article-font-x-large-fontWeight': '700',
    '--sjs-article-font-x-large-fontStyle': 'normal',
    '--sjs-article-font-x-large-fontStretch': 'normal',
    '--sjs-article-font-x-large-letterSpacing': '0',
    '--sjs-article-font-x-large-lineHeight': '56px',
    '--sjs-article-font-x-large-paragraphIndent': '0px',
    '--sjs-article-font-x-large-textCase': 'none',
    '--sjs-article-font-large-textDecoration': 'none',
    '--sjs-article-font-large-fontWeight': '700',
    '--sjs-article-font-large-fontStyle': 'normal',
    '--sjs-article-font-large-fontStretch': 'normal',
    '--sjs-article-font-large-letterSpacing': '0',
    '--sjs-article-font-large-lineHeight': '40px',
    '--sjs-article-font-large-paragraphIndent': '0px',
    '--sjs-article-font-large-textCase': 'none',
    '--sjs-article-font-medium-textDecoration': 'none',
    '--sjs-article-font-medium-fontWeight': '700',
    '--sjs-article-font-medium-fontStyle': 'normal',
    '--sjs-article-font-medium-fontStretch': 'normal',
    '--sjs-article-font-medium-letterSpacing': '0',
    '--sjs-article-font-medium-lineHeight': '32px',
    '--sjs-article-font-medium-paragraphIndent': '0px',
    '--sjs-article-font-medium-textCase': 'none',
    '--sjs-article-font-default-textDecoration': 'none',
    '--sjs-article-font-default-fontWeight': '400',
    '--sjs-article-font-default-fontStyle': 'normal',
    '--sjs-article-font-default-fontStretch': 'normal',
    '--sjs-article-font-default-letterSpacing': '0',
    '--sjs-article-font-default-lineHeight': '28px',
    '--sjs-article-font-default-paragraphIndent': '0px',
    '--sjs-article-font-default-textCase': 'none',
    '--sjs-general-backcolor-dim': 'rgba(243, 243, 243, 1)',
    '--sjs-primary-backcolor': 'rgba(25, 179, 148, 1)',
    '--sjs-primary-backcolor-dark': 'rgba(20, 164, 139, 1)',
    '--sjs-primary-backcolor-light': 'rgba(25, 179, 148, 0.1)',
    '--sjs-primary-forecolor': 'rgba(255, 255, 255, 1)',
    '--sjs-primary-forecolor-light': 'rgba(255, 255, 255, 0.25)',
    '--sjs-special-red': 'rgba(229, 10, 62, 1)',
    '--sjs-special-red-light': 'rgba(229, 10, 62, 0.1)'
  },
  headerView: 'advanced'
};

const surveyModel = {
  title: 'Sell My Vehicle',
  description: 'We offer a Lightening Fast Appraisal and payment process! Sell your vehicle to us TODAY and see what all the hype is about!',
  pages: [
    {
      name: 'vinEntry',
      elements: [
        {
          type: 'text',
          name: 'vin',
          title: 'Enter VIN Number',
          isRequired: true,
          validators: [
            { type: 'text', text: 'VIN must be 17 characters', minLength: 17, maxLength: 17 }
          ],
          maxWidth: '100%'
        },
        {
          type: 'html',
          name: 'vinError',
          visibleIf: '{vinError} notempty',
          html: '<span class="error">{vinError}</span>',
          maxWidth: '100%'
        }
      ]
    },
    {
      name: 'vehicleDetails',
      elements: [
        { type: 'text', name: 'year', title: 'Year', maxWidth: '100%' },
        { type: 'text', name: 'make', title: 'Make', maxWidth: '100%' },
        { type: 'text', name: 'model', title: 'Model', maxWidth: '100%' }
      ]
    },
    {
      name: 'page1',
      elements: [
        {
          type: 'radiogroup',
          name: 'fuelType',
          title: 'Is this a Gas, Diesel, Hybrid, or Electric Vehicle?',
          choices: [
            { value: 'gas', text: 'Gas' },
            { value: 'diesel', text: 'Diesel' },
            { value: 'hybrid', text: 'Hybrid' },
            { value: 'electric', text: 'Electric' }
          ],
          maxWidth: '100%',
          indent: 1
        }
      ]
    },
    {
      name: 'page2',
      elements: [
        { type: 'text', name: 'mileage', title: 'How Many Miles Does Your Vehicle Have?', maxWidth: '100%', indent: 1 },
        { type: 'text', name: 'trimLevel', title: 'What Trim Level Is Your Vehicle? (if known)', maxWidth: '100%', indent: 1 },
        { type: 'text', name: 'color', title: 'What Color Is Your Vehicle? (ie. red, blue, gray, or specific name)', maxWidth: '100%' },
        { type: 'text', name: 'motorSize', title: 'What Size Motor (if known)? For EVs: single or dual motor and battery range', maxWidth: '100%', indent: 1 }
      ]
    },
    {
      name: 'page3',
      elements: [
        {
          type: 'radiogroup',
          name: 'intent',
          state: 'expanded',
          title: 'Are You Planning On...',
          choices: [
            { value: 'selling', text: 'Selling Your Vehicle' },
            { value: 'trading', text: 'Trading Your Vehicle In' },
            { value: 'both', text: 'Open To Both Selling or Trading' }
          ],
          maxWidth: '100%',
          indent: 1
        }
      ]
    },
    {
      name: 'page4',
      elements: [
        { type: 'boolean', name: 'hasInteriorFeatures', title: 'Any Interior Features You Would Like To Highlight? (ie. tech package, Leather etc.)', maxWidth: '100%', indent: 1 },
        { type: 'text', name: 'interiorFeatures', visibleIf: '{hasInteriorFeatures} = true', title: 'What Are Those Features?', maxWidth: '100%', indent: 1 },
        {
          type: 'rating',
          name: 'interiorCondition',
          state: 'expanded',
          title: 'How Would You Rate The Condition Of Your Interior? (1 = missing trim, torn seats, stains; 10 = like new)',
          showCommentArea: true,
          commentPlaceholder: 'Example: I have kids and they stained the back carpets...',
          rateCount: 10,
          rateMax: 10,
          maxWidth: '100%'
        }
      ]
    },
    {
      name: 'page5',
      elements: [
        { type: 'boolean', name: 'hasExteriorFeatures', title: 'Are There Any Exterior Features You Would Like To Highlight?', maxWidth: '100%', indent: 1 },
        { type: 'text', name: 'exteriorFeatures', visibleIf: '{hasExteriorFeatures} = true', title: 'What Are Those Features?', maxWidth: '100%', indent: 1 },
        {
          type: 'rating',
          name: 'bodyCondition',
          title: 'How Would You Rate The Body Condition Of Your Vehicle? (1 = total hail damage or major body work needed; 10 = like new)',
          showCommentArea: true,
          rateCount: 10,
          rateMax: 10,
          maxWidth: '100%'
        },
        { type: 'boolean', name: 'needsWindshield', title: 'Does The Vehicle Need A New Windshield?', maxWidth: '100%', indent: 1 }
      ]
    },
    {
      name: 'page6',
      elements: [
        { type: 'boolean', name: 'needsTires', title: 'Does Your Vehicle Need New Tires?', maxWidth: '100%', indent: 1 },
        { type: 'boolean', name: 'hasWarningLights', title: 'Does Your Vehicle Have Any Warning Lights? (ie. check engine light, maintenance light etc.)', maxWidth: '100%', indent: 1 },
        { type: 'text', name: 'warningLightsDetails', visibleIf: '{hasWarningLights} = true', title: 'If Yes, Please Explain The Light And Issue.', maxWidth: '100%', indent: 1 },
        { type: 'boolean', name: 'hasMechanicalIssues', title: 'Are There Any Mechanical Issues We Should Be Aware Of?', maxWidth: '100%', indent: 1 },
        { type: 'text', name: 'mechanicalIssuesDetails', visibleIf: '{hasMechanicalIssues} = true', title: 'If There Are Issues, Please Explain. (This does not mean we will not buy your vehicle)', maxWidth: '100%', indent: 1 }
      ]
    },
    {
      name: 'page8',
      elements: [
        {
          type: 'file',
          name: 'photos',
          title: 'Please Upload Photos For A More Accurate Offer',
          allowMultiple: true,
          acceptedTypes: 'image/jpeg,image/png,image/heic,image/gif,video/mp4,video/quicktime',
          maxSize: 10485760,
          waitForUpload: true,
          needConfirmRemoveFile: true,
          maxWidth: '100%'
        }
      ]
    },
    {
      name: 'page7',
      elements: [
        { type: 'text', name: 'fullName', title: 'What Is Your Full Name?', isRequired: true, maxWidth: '100%', indent: 1 },
        { type: 'text', name: 'email', title: 'Email', isRequired: true, inputType: 'email', maxWidth: '100%', indent: 1 },
        { type: 'text', name: 'phoneNumber', title: 'Phone Number', isRequired: true, maxWidth: '100%', indent: 1 }
      ]
    }
  ],
  showCompletePage: false,
  showProgressBar: true,
  progressBarLocation: 'top',
  widthMode: 'responsive',
  headerView: 'advanced'
};

const survey = ref(null);
const isLoading = ref(false);

try {
  survey.value = new Model(surveyModel);
  survey.value.applyTheme(themeJson);
  console.log('Survey initialized with theme:', survey.value);
} catch (error) {
  console.error('Failed to initialize Survey Model:', error);
}

onMounted(() => {
  if (!survey.value) {
    console.error('Survey not initialized');
    return;
  }

  survey.value.onValueChanged.add(async (sender, options) => {
    if (options.name === 'vin' && options.value.length === 17) {
      isLoading.value = true;
      try {
        const response = await fetch(`https://vpic.nhtsa.dot.gov/api/vehicles/decodevinvalues/${options.value}?format=json`);
        const data = await response.json();
        const result = data.Results[0];

        console.log('API Result:', result);

        if (result && result.ModelYear && result.Make && result.Model) {
          sender.setValue('year', result.ModelYear);
          sender.setValue('make', result.Make);
          sender.setValue('model', result.Model);

          console.log('Set values:', sender.data);

          await new Promise(resolve => setTimeout(resolve, 1500));

          sender.setVariable('vinError', '');
          sender.nextPage();
        } else {
          sender.setVariable('vinError', 'Invalid VIN - please check and try again');
          sender.render();
        }
      } catch (error) {
        console.error('VIN API error:', error);
        sender.setVariable('vinError', 'Error fetching VIN data - please try again');
        sender.render();
      } finally {
        isLoading.value = false;
      }
    }
  });

  survey.value.setVariable('vinError', '');

  survey.value.onComplete.add((sender) => {
    const formData = sender.data;
    router.post('/intake/store', formData, {
      onSuccess: () => {
        console.log('Form submitted successfully');
        survey.value.clear(); // Reset form
      },
      onError: (errors) => console.error('Submission errors:', errors)
    });
  });
});
</script>

<style scoped>
.survey-container {
  position: relative;
}
.spinner-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 20;
}
.spinner {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
.spinner-icon {
  width: 24px;
  height: 24px;
  border: 4px solidર
  #ccc;
  border-top: 4px solid #007bff;
  border-radius: 50%;
  animation: spin 1s ease-in-out infinite;
  margin-right: 0.75rem;
}
.error {
  color: red;
  font-size: 0.9em;
}
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>