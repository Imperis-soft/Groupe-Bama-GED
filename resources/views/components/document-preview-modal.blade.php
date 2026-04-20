@props([])

<div x-data="documentPreview()" class="fixed inset-0 z-50 overflow-y-auto" x-show="showModal" style="display: none;"
     @open-preview.window="openWithData($event.detail)">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal()" x-show="showModal"></div>

        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full"
             x-show="showModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

            <!-- Header -->
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-file-word text-2xl text-orange-600"></i>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900" x-text="document.title"></h3>
                            <p class="text-sm text-gray-500 font-mono" x-text="document.reference"></p>
                        </div>
                    </div>
                    <button @click="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="bg-gray-50 px-4 py-4 sm:p-6 max-h-96 overflow-y-auto">
                <div class="space-y-4">
                    <!-- Métadonnées -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="font-bold text-gray-600">Catégorie:</span>
                            <p class="text-gray-900" x-text="document.category?.name || 'Général'"></p>
                        </div>
                        <div>
                            <span class="font-bold text-gray-600">Statut:</span>
                            <span class="px-2 py-1 rounded text-xs font-bold"
                                  :class="{
                                      'bg-gray-100 text-gray-600': document.status === 'draft',
                                      'bg-blue-100 text-blue-600': document.status === 'review',
                                      'bg-green-100 text-green-600': document.status === 'approved',
                                      'bg-orange-100 text-orange-600': document.status === 'archived'
                                  }"
                                  x-text="getStatusLabel(document.status)"></span>
                        </div>
                        <div>
                            <span class="font-bold text-gray-600">Créé par:</span>
                            <p class="text-gray-900" x-text="document.creator?.full_name || 'Système'"></p>
                        </div>
                        <div>
                            <span class="font-bold text-gray-600">Créé le:</span>
                            <p class="text-gray-900" x-text="formatDate(document.created_at)"></p>
                        </div>
                    </div>

                    <!-- Tags -->
                    <div x-show="document.tags" class="flex flex-wrap gap-2">
                        <span class="font-bold text-gray-600 text-sm">Tags:</span>
                        <template x-for="tag in document.tags?.split(',')" :key="tag">
                            <span class="px-2 py-1 bg-orange-100 text-orange-700 rounded text-xs font-medium" x-text="tag.trim()"></span>
                        </template>
                    </div>

                    <!-- Confidentialité -->
                    <div x-show="document.is_confidential" class="flex items-center gap-2">
                        <i class="fa-solid fa-lock text-red-500"></i>
                        <span class="text-red-600 font-bold text-sm">Document confidentiel</span>
                    </div>

                    <!-- Aperçu du contenu -->
                    <div x-show="document.content_text" class="bg-white p-4 rounded-lg border border-gray-200">
                        <h4 class="font-bold text-gray-700 mb-2">Aperçu du contenu:</h4>
                        <div class="text-gray-600 text-sm leading-relaxed max-h-32 overflow-y-auto"
                             x-text="document.content_text?.substring(0, 500) + (document.content_text?.length > 500 ? '...' : '')">
                        </div>
                    </div>

                    <!-- Version actuelle -->
                    <div class="bg-white p-4 rounded-lg border border-gray-200">
                        <h4 class="font-bold text-gray-700 mb-2">Informations de version:</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="font-bold text-gray-600">Version:</span>
                                <span class="text-gray-900" x-text="document.version"></span>
                            </div>
                            <div>
                                <span class="font-bold text-gray-600">Dernière modification:</span>
                                <span class="text-gray-900" x-text="formatDate(document.updated_at)"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-3">
                <a :href="'/documents/' + document.id" class="inline-flex justify-center w-full sm:w-auto px-4 py-2 bg-orange-600 border border-transparent rounded-md font-bold text-white text-sm hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition">
                    <i class="fa-solid fa-eye mr-2"></i>Voir le document complet
                </a>
                <a :href="'/documents/' + document.id + '/edit'" class="inline-flex justify-center w-full sm:w-auto px-4 py-2 bg-white border border-gray-300 rounded-md font-bold text-gray-700 text-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition">
                    <i class="fa-solid fa-edit mr-2"></i>Éditer
                </a>
                <button @click="closeModal()" class="inline-flex justify-center w-full sm:w-auto px-4 py-2 bg-gray-300 border border-transparent rounded-md font-bold text-gray-700 text-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition">
                    Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function documentPreview() {
    return {
        showModal: false,
        document: null,

        openWithData(docData) {
            this.document = docData;
            this.openModal();
        },

        openModal() {
            this.showModal = true;
            document.body.style.overflow = 'hidden';
        },

        closeModal() {
            this.showModal = false;
            document.body.style.overflow = 'auto';
        },

        getStatusLabel(status) {
            const labels = {
                'draft': 'Brouillon',
                'review': 'En révision',
                'approved': 'Approuvé',
                'archived': 'Archivé'
            };
            return labels[status] || status;
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }
}
</script>