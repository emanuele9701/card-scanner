import { ref } from 'vue'

const modalState = ref({
    isOpen: false,
    type: 'info', // 'info', 'success', 'warning', 'error', 'confirm'
    title: '',
    message: '',
    confirmText: 'Conferma',
    cancelText: 'Annulla',
    onConfirm: null,
    onCancel: null
})

export function useModal() {
    /**
     * Show a confirmation modal
     * @param {string} message - The message to display
     * @param {string} title - Optional title (default: 'Conferma')
     * @param {object} options - Optional settings (confirmText, cancelText)
     * @returns {Promise<boolean>} - Resolves to true if confirmed, false if canceled
     */
    const showConfirm = (message, title = 'Conferma', options = {}) => {
        return new Promise((resolve) => {
            modalState.value = {
                isOpen: true,
                type: 'confirm',
                title: title,
                message: message,
                confirmText: options.confirmText || 'Conferma',
                cancelText: options.cancelText || 'Annulla',
                onConfirm: () => {
                    closeModal()
                    resolve(true)
                },
                onCancel: () => {
                    closeModal()
                    resolve(false)
                }
            }
        })
    }

    /**
     * Show an alert modal
     * @param {string} message - The message to display
     * @param {string} type - Type of alert: 'info', 'success', 'warning', 'error'
     * @param {string} title - Optional title
     * @returns {Promise<void>}
     */
    const showAlert = (message, type = 'info', title = '') => {
        return new Promise((resolve) => {
            const defaultTitles = {
                success: 'Successo',
                error: 'Errore',
                warning: 'Attenzione',
                info: 'Informazione'
            }

            modalState.value = {
                isOpen: true,
                type: type,
                title: title || defaultTitles[type] || 'Informazione',
                message: message,
                confirmText: 'OK',
                cancelText: '',
                onConfirm: () => {
                    closeModal()
                    resolve()
                },
                onCancel: null
            }
        })
    }

    const closeModal = () => {
        modalState.value.isOpen = false
    }

    return {
        modalState,
        showConfirm,
        showAlert,
        closeModal
    }
}
