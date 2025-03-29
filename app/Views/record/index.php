<?= $this->extend('template3/index') ?>
<?= $this->section('page-content') ?>

<h1>Data Records</h1>

<div class="date"></div>

<!-- Project Records -->
<div class="recent-orders">
    <div style="display: flex; align-items: center;">
        <h2 style="flex: 1;">Projects</h2>
        <form action="<?= site_url('record/createProject') ?>" method="POST" style="background: transparent; border: none; padding: 0;">
            <input type="hidden" name="postValue" value="Project">
            <button type="submit" class="btn btn-primary" style="display: flex; align-items: center; gap: 5px; background: transparent; border: none; color: #007bff; font-size: 14px; cursor: pointer;">
                Add Records <i class="fas fa-arrow-right"></i>
            </button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Project Number</th>
                <th>Project Name</th>
                <th>Creation Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($jobRecords as $jobRecord): ?>
                <tr>
                    <td><?= esc($jobRecord['job_number']) ?></td>
                    <td><?= esc($jobRecord['job_name']) ?></td>
                    <td><?= esc($jobRecord['job_entryDateTime']) ?></td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            <a href="" class="btn btn-warning">View</a>
                            <a href="" class="btn btn-warning">Edit</a>
                            <a href="" class="btn btn-danger">Delete</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- FontAwesome for the right arrow icon -->
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

<!-- Weld Metal Records -->
<div class="recent-orders">
    <div style="display: flex; align-items: center;">
        <h2 style="flex: 1;">Weld Metals</h2>
        <form action="<?= site_url('record/createWeldMetal') ?>" method="POST" style="background: transparent; border: none; padding: 0;">
            <input type="hidden" name="postValue" value="Weld Metal">
            <button type="submit" class="btn btn-primary" style="display: flex; align-items: center; gap: 5px; background: transparent; border: none; color: #007bff; font-size: 14px; cursor: pointer;">
                Add Records <i class="fas fa-arrow-right"></i>
            </button>
        </form>
    </div>
    <table>
        <thead>
            <tr>
                <th>Weld Metal Batch Number</th>
                <th>Weld Metal Type</th>
                <th>Weld Metal Cert. No</th>
                <th>Creation Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($weldMetalRecords as $weldMetalRecord): ?>
                <tr>
                    <td><?= esc($weldMetalRecord['weldMetal_batchNumber']) ?></td>
                    <td><?= esc($weldMetalRecord['weldMetal_type']) ?></td>
                    <td><?= esc($weldMetalRecord['weldMetal_certNo']) ?></td>
                    <td><?= esc($weldMetalRecord['weldMetal_creationDateTime']) ?></td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            <!-- <a href="<?= site_url('record/edit/' . $weldMetalRecord['weldMetal_batchNumber']) ?>" class="btn btn-warning">Edit</a> -->
                            <a href="" class="btn btn-warning">View</a>
                            <a href="" class="btn btn-warning">Edit</a>
                            <!-- <a href="<?= site_url('record/delete/' . $weldMetalRecord['weldMetal_batchNumber']) ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this record?')">Delete</a> -->
                            <a href="" class="btn btn-danger">Delete</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>

    </table>
</div>

<!-- Welder Records -->
<div class="recent-orders">
    <div style="display: flex; align-items: center;">
        <h2 style="flex: 1;">Welders</h2>
        <form action="<?= site_url('record/createWeldMetal') ?>" method="POST" style="background: transparent; border: none; padding: 0;">
            <input type="hidden" name="postValue" value="Weld Metal">
            <button type="submit" class="btn btn-primary" style="display: flex; align-items: center; gap: 5px; background: transparent; border: none; color: #007bff; font-size: 14px; cursor: pointer;">
                Add Records <i class="fas fa-arrow-right"></i>
            </button>
        </form>
    </div>
    <table>
        <thead>
            <tr>
                <th>Welder Name</th>
                <th>Welder Code</th>
                <th>Welder Date of Birth</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($welderRecords as $welderRecord): ?>
                <tr>
                    <td><?= esc($welderRecord['Name']) ?></td>
                    <td><?= esc($welderRecord['welder_code']) ?></td> 
                    <td><?= esc($welderRecord['welder_dob']) ?></td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            <!-- <a href="<?= site_url('record/edit/' . $welderRecord['ID']) ?>" class="btn btn-warning">Edit</a> -->
                            <a href="" class="btn btn-warning">View</a>
                            <a href="" class="btn btn-warning">Edit</a>
                            <!-- <a href="<?= site_url('record/delete/' . $welderRecord['ID']) ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this record?')">Delete</a> -->
                            <a href="" class="btn btn-danger">Delete</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>

    </table>
</div>

<!-- Machine Records -->
<div class="recent-orders">
    <div style="display: flex; align-items: center;">
        <h2 style="flex: 1;">Machines</h2>
        <form action="<?= site_url('record/createWeldMetal') ?>" method="POST" style="background: transparent; border: none; padding: 0;">
            <input type="hidden" name="postValue" value="Weld Metal">
            <button type="submit" class="btn btn-primary" style="display: flex; align-items: center; gap: 5px; background: transparent; border: none; color: #007bff; font-size: 14px; cursor: pointer;">
                Add Records <i class="fas fa-arrow-right"></i>
            </button>
        </form>
    </div>
    <table>
        <thead>
            <tr>
                <th>Machine ID</th>
                <th>Machine Serial No</th>
                <th>Machine Cert. No</th>
                <th>Machine Area</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        </tbody>

    </table>
</div>

<!-- Inspector Records -->
<div class="recent-orders">
    <div style="display: flex; align-items: center;">
        <h2 style="flex: 1;">Inspectors</h2>
        <form action="<?= site_url('record/createWeldMetal') ?>" method="POST" style="background: transparent; border: none; padding: 0;">
            <input type="hidden" name="postValue" value="Weld Metal">
            <button type="submit" class="btn btn-primary" style="display: flex; align-items: center; gap: 5px; background: transparent; border: none; color: #007bff; font-size: 14px; cursor: pointer;">
                Add Records <i class="fas fa-arrow-right"></i>
            </button>
        </form>
    </div>
    <table>
        <thead>
            <tr>
                <th>Inspector ID</th>
                <th>Inspector Name</th>
                <th>Inspector Code</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($inspectorRecords as $inspectorRecord): ?>
                <tr>
                    <td><?= esc($inspectorRecord['inspector_name']) ?></td>
                    <td><?= esc($inspectorRecord['inspector_certNo']) ?></td> 
                    <td><?= esc($inspectorRecord['inspector_DoB']) ?></td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            <!-- <a href="<?= site_url('record/edit/' . $welderRecord['ID']) ?>" class="btn btn-warning">Edit</a> -->
                            <a href="" class="btn btn-warning">View</a>
                            <a href="" class="btn btn-warning">Edit</a>
                            <!-- <a href="<?= site_url('record/delete/' . $welderRecord['ID']) ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this record?')">Delete</a> -->
                            <a href="" class="btn btn-danger">Delete</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>

    </table>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<?= $this->endSection() ?>