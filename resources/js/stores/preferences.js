import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

// Global preferences store - NO DARK MODE
const preferences = ref({
    font_style: 'inter',
    font_size: 'medium'
    // REMOVED: dark_mode: false
})

// Load preferences from localStorage
export function loadPreferences() {
    const saved = localStorage.getItem('userPreferences')
    if (saved) {
        try {
            preferences.value = { ...preferences.value, ...JSON.parse(saved) }
            applyGlobalPreferences()
        } catch (error) {
            console.error('Error loading preferences:', error)
        }
    }
}

// Apply preferences to the entire document
export function applyGlobalPreferences() {
    console.log('Applying global preferences:', preferences.value)
    
    // Apply font family
    const fontFamily = getFontFamily(preferences.value.font_style)
    document.documentElement.style.setProperty('--font-family', fontFamily)
    document.body.style.fontFamily = fontFamily
    
    // Apply font size
    const fontSize = getFontSize(preferences.value.font_size)
    document.documentElement.style.setProperty('--font-size', fontSize)
    document.body.style.fontSize = fontSize
    
    // REMOVED: All dark mode logic
    
    // Force style recalculation
    document.body.style.display = 'none'
    document.body.offsetHeight
    document.body.style.display = ''
}

// Save preferences globally
export async function saveGlobalPreferences(newPreferences) {
    preferences.value = { ...preferences.value, ...newPreferences }
    
    // Save to localStorage
    localStorage.setItem('userPreferences', JSON.stringify(preferences.value))
    
    // Apply immediately
    applyGlobalPreferences()
    
    // Save to backend using Inertia (handles CSRF automatically)
    try {
        await router.post('/admin/utilities/preferences', preferences.value)
    } catch (error) {
        console.error('Error saving to backend:', error)
    }
}

// Helper functions
function getFontFamily(fontStyle) {
    const fonts = {
        inter: '"Inter", system-ui, -apple-system, sans-serif',
        arial: 'Arial, Helvetica, sans-serif',
        roboto: '"Roboto", system-ui, -apple-system, sans-serif'
    }
    return fonts[fontStyle] || fonts.inter
}

function getFontSize(fontSize) {
    const sizes = {
        small: '14px',
        medium: '16px',
        large: '18px'
    }
    return sizes[fontSize] || sizes.medium
}

export function usePreferences() {
    return {
        preferences,
        saveGlobalPreferences,
        applyGlobalPreferences,
        loadPreferences
    }
}