<div class="modal fade" id="modalAddRole" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    Add Role
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <form id="formAddRole">

                @csrf

                <div class="modal-body">

                    <div class="mb-3">

                        <label class="form-label">
                            Role Name
                        </label>

                        <input
                            type="text"
                            name="role_name"
                            class="form-control"
                            placeholder="Enter role name">

                        <div class="invalid-feedback"></div>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Status
                        </label>

                        <select
                            name="status"
                            class="form-select">

                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>

                        </select>

                        <div class="invalid-feedback"></div>

                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary">
                        Save
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>