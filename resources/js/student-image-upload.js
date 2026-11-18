document.addEventListener('alpine:init', () => {
    window.Alpine.data('privateStudentImageUpload', (field) => ({
        field,
        cameraOpen: false,
        facingMode: 'environment',
        stream: null,
        cameraError: '',
        uploadError: '',
        uploading: false,

        destroy() {
            this.stopCamera();
        },

        async startCamera() {
            this.cameraError = '';
            this.stopCamera();

            if (!navigator.mediaDevices?.getUserMedia) {
                this.cameraError = 'Camera capture is unavailable in this browser. Use the file picker instead.';
                return;
            }

            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    audio: false,
                    video: { facingMode: { ideal: this.facingMode } },
                });
                this.cameraOpen = true;
                await this.$nextTick();
                this.$refs.cameraVideo.srcObject = this.stream;
                await this.$refs.cameraVideo.play();
            } catch (error) {
                this.stopCamera();
                this.cameraOpen = false;
                this.cameraError = error?.name === 'NotAllowedError'
                    ? 'Camera permission was denied. Allow camera access or choose an image file.'
                    : 'Unable to open the camera. Use the file picker instead.';
            }
        },

        async switchCamera() {
            this.facingMode = this.facingMode === 'environment' ? 'user' : 'environment';
            await this.startCamera();
        },

        capture() {
            const video = this.$refs.cameraVideo;
            if (!video?.videoWidth || !video?.videoHeight) {
                this.cameraError = 'The camera is not ready yet. Please try again.';
                return;
            }

            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

            canvas.toBlob((blob) => {
                if (!blob) {
                    this.cameraError = 'The photo could not be captured. Please try again.';
                    return;
                }

                const file = new File([blob], `${this.field}-${Date.now()}.jpg`, { type: 'image/jpeg' });
                this.upload(file);
                this.closeCamera();
            }, 'image/jpeg', 0.9);
        },

        uploadDropped(event) {
            const file = event.dataTransfer?.files?.[0];
            if (!file) return;

            if (!['image/jpeg', 'image/png'].includes(file.type)) {
                this.uploadError = 'Choose a JPG, JPEG, or PNG image.';
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                this.uploadError = 'The image must not exceed 5 MB.';
                return;
            }

            this.upload(file);
        },

        upload(file) {
            this.uploadError = '';
            this.uploading = true;
            this.$wire.upload(this.field, file,
                () => { this.uploading = false; },
                () => {
                    this.uploading = false;
                    this.uploadError = 'The image upload failed. Please try again.';
                },
            );
        },

        closeCamera() {
            this.cameraOpen = false;
            this.stopCamera();
        },

        stopCamera() {
            if (this.stream) {
                this.stream.getTracks().forEach((track) => track.stop());
                this.stream = null;
            }

            if (this.$refs.cameraVideo) {
                this.$refs.cameraVideo.srcObject = null;
            }
        },

        clearSelection() {
            this.$wire.$set(this.field, null);
            this.uploadError = '';
        },
    }));
});
