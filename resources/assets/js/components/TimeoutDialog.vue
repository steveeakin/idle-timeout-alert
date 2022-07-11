<template>
    <div class="fixed z-10 inset-0 overflow-y-auto ease-out duration-400" v-if="showModal">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <!-- This element is to trick the browser into centering the modal contents. -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen"></span>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full" role="dialog" aria-modal="true" aria-labelledby="modal-headline">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="">
                          <div class="mb-4">
                            <span class="block text-gray-700 text-sm font-bold mb-2">Are you still here?</span>
                        </div>

                        <div class="mb-4">
                            <span class="block text-gray-700 text-sm font-bold mb-2">You've been inactive for a bit now... If you don't do something, you will be automatically logged out in:</span>
                            <br />
                            <span class="block text-gray-700 text-sm font-bold mb-2 text-danger">{{ this.remainingTime }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <span class="flex w-full rounded-md shadow-sm sm:ml-3 sm:w-auto">
                        <button type="button" class="inline-flex justify-center w-full rounded-md border border-transparent px-4 py-2 bg-green-600 text-base leading-6 font-medium text-white shadow-sm hover:bg-green-500 focus:outline-none focus:border-green-700 focus:shadow-outline-green transition ease-in-out duration-150 sm:text-sm sm:leading-5" @click="resetTimeout">
                            I'm still here...
                        </button>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <jet-confirmation-modal :show="showModal" @close="resetTimeout()">
        <template #title>
            Are you still here?
        </template>

        <template #content>
            <p class="text-center">You've been inactive for a bit now... If you don't do something, you will be automatically logged out in:</p>
            <p class="text-center">
                <span class="text-danger" style="font-size: 1.5em;">{{ this.remainingTime }}</span>
                seconds
            </p>
        </template>

        <template #footer>
            <jet-secondary-button @click.native="showModal = false">
                Nevermind
            </jet-secondary-button>
        </template>
    </jet-confirmation-modal>
</template>

<script type="text/javascript">
    const timeToAlert = 59;

    export default {
        name: "TimeoutDialog",
        props: ["keepAlive", "ignoreActivity","showModal"],
        data() {
            return {
                showModal: false,
                remainingTime: undefined,
                isIdle: false,
                timer: setInterval(() => {
                    if (this.remainingTime === undefined) return;
                    this.remainingTime = this.remainingTime <= 0 ? 0 : this.remainingTime - 1;

                    if (this.remainingTime <= 0) {
                        // session should be expired at this point
                        // redirect to /login
                        setTimeout(() => {
                            window.location.replace("/login");
                        }, 1500);
                        return;
                    }

                    if (this.remainingTime <= timeToAlert) {
                        if (!this.showModal) {
                            if (this.keepAlive || (this.ignoreActivity !== true && !this.isIdle)) {
                                return this.resetTimeout();
                            }

                            // another check to make sure if we still need to show modal
                            this.checkTimeout(remainingTime => {
                                if (remainingTime <= timeToAlert) {
                                    this.showModal = true;
                                }
                            });
                        } else {
                            // modal is already shown, but maybe timeout reset in another tab or window
                            // check again every 5 seconds
                            if (this.remainingTime % 5 === 1) {
                                this.checkTimeout(remainingTime => {
                                    if (remainingTime > timeToAlert) {
                                        this.showModal = false;
                                    }
                                });
                            }
                        }
                    }
                }, 1000)
            };
        },
        mounted: function () {
            this.checkTimeout();
        },
        onIdle() {
            this.isIdle = true;
        },
        onActive() {
            this.isIdle = false;
        },
        methods: {
            checkTimeout(callback) {
                axios
                    .get("/idle-timeout-alert/check")
                    .then(response => {
                        this.remainingTime = response.data;

                        if (callback) {
                            callback(response.data);
                        }
                    })
                    .catch(e => {
                    });
            },
            resetTimeout() {
                axios
                    .post("/idle-timeout-alert/ping")
                    .then(response => {
                        this.showModal = false;
                        this.remainingTime = response.data;
                    })
                    .catch(e => {
                    });
            },
        }
    };
</script>