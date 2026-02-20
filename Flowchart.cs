<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Electronics Inventory System - Flowchart</title>
    <script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 100%;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1em;
        }
        .legend {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
        }
        .legend h3 {
            margin-top: 0;
            color: #667eea;
        }
        .legend-items {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .legend-color {
            width: 30px;
            height: 30px;
            border-radius: 5px;
            border: 2px solid #333;
        }
        .color-start { background: #667eea; }
        .color-process { background: #4CAF50; }
        .color-decision { background: #ff9800; }
        .color-database { background: #2196F3; }
        .color-error { background: #f44336; }
        .color-success { background: #8bc34a; }
        .mermaid {
            text-align: center;
            background: white;
            padding: 20px;
            border-radius: 10px;
            overflow-x: auto;
        }
        .info-boxes {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .info-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .info-box h3 {
            margin-top: 0;
            font-size: 1.2em;
            border-bottom: 2px solid rgba(255,255,255,0.3);
            padding-bottom: 10px;
        }
        .info-box ul {
            list-style: none;
            padding: 0;
            margin: 10px 0 0 0;
        }
        .info-box li {
            padding: 5px 0;
            padding-left: 20px;
            position: relative;
        }
        .info-box li:before {
            content: "✓";
            position: absolute;
            left: 0;
            font-weight: bold;
        }
        .download-btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            margin: 20px auto;
            display: block;
            width: fit-content;
            font-weight: bold;
            transition: all 0.3s;
        }
        .download-btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .download-btn, .legend {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Electronics Inventory System</h1>
        <p class="subtitle">Complete System Flowchart - All Modules & Processes</p>
        
        <div class="legend">
            <h3>🎨 Flowchart Legend</h3>
            <div class="legend-items">
                <div class="legend-item">
                    <div class="legend-color color-start"></div>
                    <span><strong>Start/End</strong> - Entry & Exit Points</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color color-process"></div>
                    <span><strong>Process</strong> - Actions & Operations</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color color-decision"></div>
                    <span><strong>Decision</strong> - Conditional Branches</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color color-database"></div>
                    <span><strong>Database</strong> - Data Storage</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color color-error"></div>
                    <span><strong>Error</strong> - Error States</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color color-success"></div>
                    <span><strong>Success</strong> - Successful Operations</span>
                </div>
            </div>
        </div>

        <div class="mermaid">
graph TB
    Start([System Start]) --> Login[User Login]
    Login --> Auth{Authentication<br/>Successful?}
    Auth -->|No| Login
    Auth -->|Yes| RoleCheck{User Role?}
    
    RoleCheck -->|Staff| StaffDash[Staff Dashboard]
    RoleCheck -->|Admin| AdminDash[Admin Dashboard]
    RoleCheck -->|Super Admin| SuperDash[Super Admin Dashboard]
    
    %% Staff Functions
    StaffDash --> ViewAssets[View Assets]
    StaffDash --> ViewAssignments[View My Assignments]
    StaffDash --> ViewProfile[View Profile]
    
    %% Admin Functions
    AdminDash --> AssetMgmt[Asset Management]
    AdminDash --> StaffMgmt[Staff Management]
    AdminDash --> AssignMgmt[Assignment Management]
    AdminDash --> InspectionMgmt[Inspection Management]
    AdminDash --> Reports[Generate Reports]
    
    %% Super Admin Functions
    SuperDash --> AllAdmin[All Admin Functions]
    SuperDash --> UserMgmt[User Management]
    SuperDash --> CategoryMgmt[Category Management]
    SuperDash --> SupplierMgmt[Supplier Management]
    SuperDash --> SystemSettings[System Settings]
    
    %% Asset Management Flow
    AssetMgmt --> AssetActions{Asset Action?}
    AssetActions -->|Add New| AddAsset[Add Asset Form]
    AssetActions -->|Edit| EditAsset[Edit Asset]
    AssetActions -->|View| ViewAssetDetail[View Asset Details]
    AssetActions -->|Import| ImportAssets[Bulk Import Assets]
    
    AddAsset --> AssetForm[Fill Asset Information:<br/>- Asset Tag<br/>- Name & Model<br/>- Category<br/>- Serial Number<br/>- Purchase Date<br/>- Cost<br/>- Supplier<br/>- Location<br/>- Status]
    AssetForm --> SaveAsset{Save?}
    SaveAsset -->|Yes| LogAsset[Log Activity:<br/>CREATE Asset]
    SaveAsset -->|No| AssetMgmt
    LogAsset --> AssetSuccess[Asset Created Successfully]
    AssetSuccess --> AssetMgmt
    
    %% Staff Management Flow
    StaffMgmt --> StaffActions{Staff Action?}
    StaffActions -->|Add New| AddStaff[Add Staff Form]
    StaffActions -->|Edit| EditStaff[Edit Staff]
    StaffActions -->|View| ViewStaffDetail[View Staff Details]
    
    AddStaff --> StaffForm[Fill Staff Information:<br/>- Full Name<br/>- Email & Phone<br/>- Employee ID<br/>- Position<br/>- Department<br/>- Section<br/>- Floor<br/>- Date Joined]
    StaffForm --> DeptSelect[Select Department]
    DeptSelect --> SectionAuto[Auto-populate Sections<br/>from department_sections]
    SectionAuto --> SaveStaff{Save?}
    SaveStaff -->|Yes| LogStaff[Log Activity:<br/>CREATE Staff]
    SaveStaff -->|No| StaffMgmt
    LogStaff --> StaffSuccess[Staff Added Successfully]
    StaffSuccess --> StaffMgmt
    
    %% Assignment Flow
    AssignMgmt --> AssignActions{Assignment Action?}
    AssignActions -->|Assign Asset| CreateAssign[Create Assignment]
    AssignActions -->|Return Asset| ReturnAsset[Process Return]
    AssignActions -->|View History| ViewAssignHistory[View Assignment History]
    
    CreateAssign --> SelectAsset[Select Available Asset]
    SelectAsset --> SelectStaff[Select Staff Member]
    SelectStaff --> AssignDetails[Enter Assignment Details:<br/>- Assignment Date<br/>- Notes<br/>- Condition]
    AssignDetails --> CheckAvail{Asset<br/>Available?}
    CheckAvail -->|No| ErrorAvail[Error: Asset Not Available]
    CheckAvail -->|Yes| UpdateAssetStatus[Update Asset Status<br/>to 'assigned']
    UpdateAssetStatus --> SaveAssign[Save Assignment]
    SaveAssign --> LogAssign[Log Activity:<br/>CREATE Assignment]
    LogAssign --> AssignSuccess[Assignment Created]
    AssignSuccess --> AssignMgmt
    
    ReturnAsset --> SelectReturn[Select Assignment to Return]
    SelectReturn --> ReturnDetails[Enter Return Details:<br/>- Return Date<br/>- Return Condition<br/>- Notes]
    ReturnDetails --> UpdateReturn[Update Assignment<br/>Status to 'returned']
    UpdateReturn --> FreeAsset[Update Asset Status<br/>to 'available']
    FreeAsset --> LogReturn[Log Activity:<br/>RETURN Asset]
    LogReturn --> ReturnSuccess[Return Processed]
    ReturnSuccess --> AssignMgmt
    
    %% Inspection Flow
    InspectionMgmt --> InspectActions{Inspection Action?}
    InspectActions -->|New Inspection| NewInspect[Create New Inspection]
    InspectActions -->|View Inspections| ViewInspect[View Inspection List]
    InspectActions -->|View Details| InspectDetail[View Inspection Details]
    InspectActions -->|Schedule| ScheduleInspect[Schedule Quarterly Inspections]
    
    NewInspect --> SelectInspectAsset[Select Asset to Inspect]
    SelectInspectAsset --> InspectForm[Fill Inspection Form:<br/>- Inspection Date & Quarter<br/>- Inspector Info<br/>- Subject User<br/>- Asset Details]
    InspectForm --> SecurityChecks[Security Checks:<br/>✓ Powers On?<br/>✓ Passwords Removed?<br/>✓ Company Data Removed?<br/>✓ Activation Locks Removed?]
    SecurityChecks --> PhysicalCheck[Physical Condition:<br/>- Physically Intact<br/>- Case Cracks<br/>- LCD Scratches<br/>- LCD Discoloration<br/>- Has Accessories]
    PhysicalCheck --> ComponentCheck[Component Checklist:<br/>14 Components<br/>- Missing?<br/>- Working?<br/>- Damaged?]
    ComponentCheck --> InspectComments[Add Comments:<br/>- Inspector Notes<br/>- User Feedback<br/>- Overall Condition]
    InspectComments --> CalcStatus{All Critical<br/>Checks Pass?}
    CalcStatus -->|No| FailInspect[Status: FAILED]
    CalcStatus -->|Yes| PassInspect[Status: COMPLETED]
    FailInspect --> SaveInspect[Save Inspection]
    PassInspect --> SaveInspect
    SaveInspect --> LogInspect[Log Activity:<br/>CREATE Inspection]
    LogInspect --> InspectSuccess[Inspection Saved]
    InspectSuccess --> InspectionMgmt
    
    ViewInspect --> FilterInspect[Filter by:<br/>- Quarter<br/>- Year<br/>- Status<br/>- Asset/Inspector]
    FilterInspect --> InspectList[Display Inspection List:<br/>- Date & Quarter<br/>- Asset<br/>- Inspector<br/>- Status<br/>- Pass/Fail]
    InspectList --> InspectStats[Show Statistics:<br/>- Total Inspections<br/>- This Quarter<br/>- Passed<br/>- Failed<br/>- Overdue]
    
    %% Reports Flow
    Reports --> ReportType{Report Type?}
    ReportType -->|Asset Report| AssetReport[Asset Status Report]
    ReportType -->|Assignment Report| AssignReport[Assignment History Report]
    ReportType -->|Inspection Report| InspectReport[Inspection Compliance Report]
    ReportType -->|Staff Report| StaffReport[Staff Directory Report]
    ReportType -->|Financial Report| FinReport[Asset Value Report]
    
    AssetReport --> GenReport[Generate Report]
    AssignReport --> GenReport
    InspectReport --> GenReport
    StaffReport --> GenReport
    FinReport --> GenReport
    
    GenReport --> ExportOptions{Export Format?}
    ExportOptions -->|PDF| ExportPDF[Export as PDF]
    ExportOptions -->|Excel| ExportExcel[Export as Excel]
    ExportOptions -->|CSV| ExportCSV[Export as CSV]
    
    ExportPDF --> Download[Download Report]
    ExportExcel --> Download
    ExportCSV --> Download
    Download --> Reports
    
    %% User Management (Super Admin Only)
    UserMgmt --> UserActions{User Action?}
    UserActions -->|Add User| AddUser[Create User Account]
    UserActions -->|Edit User| EditUser[Edit User Details]
    UserActions -->|Change Role| ChangeRole[Modify User Role:<br/>- Staff<br/>- Admin<br/>- Super Admin]
    UserActions -->|Deactivate| DeactivateUser[Deactivate User]
    
    AddUser --> UserForm[Enter User Details:<br/>- Username<br/>- Password<br/>- Full Name<br/>- Email<br/>- Role]
    UserForm --> SaveUser{Save?}
    SaveUser -->|Yes| LogUser[Log Activity:<br/>CREATE User]
    SaveUser -->|No| UserMgmt
    LogUser --> UserSuccess[User Created]
    UserSuccess --> UserMgmt
    
    ChangeRole --> PermCheck{Authorized?}
    PermCheck -->|No| PermDenied[Permission Denied]
    PermCheck -->|Yes| UpdateRole[Update User Role]
    UpdateRole --> LogRole[Log Activity:<br/>UPDATE User Role]
    LogRole --> RoleSuccess[Role Updated]
    RoleSuccess --> UserMgmt
    
    %% Category Management
    CategoryMgmt --> CatActions{Category Action?}
    CatActions -->|Add Category| AddCat[Add New Category]
    CatActions -->|Edit Category| EditCat[Edit Category]
    CatActions -->|Delete Category| DelCat[Delete Category]
    
    AddCat --> CatForm[Enter Category Details:<br/>- Name<br/>- Description]
    CatForm --> SaveCat[Save Category]
    SaveCat --> LogCat[Log Activity:<br/>CREATE Category]
    LogCat --> CatSuccess[Category Created]
    CatSuccess --> CategoryMgmt
    
    DelCat --> CheckAssets{Has<br/>Assets?}
    CheckAssets -->|Yes| CantDelete[Cannot Delete:<br/>Has Associated Assets]
    CheckAssets -->|No| DeleteCat[Delete Category]
    DeleteCat --> LogDelCat[Log Activity:<br/>DELETE Category]
    LogDelCat --> DelSuccess[Category Deleted]
    DelSuccess --> CategoryMgmt
    
    %% Activity Logging (Background Process)
    LogAsset -.-> ActivityLog[(Activity Log Database)]
    LogStaff -.-> ActivityLog
    LogAssign -.-> ActivityLog
    LogReturn -.-> ActivityLog
    LogInspect -.-> ActivityLog
    LogUser -.-> ActivityLog
    LogRole -.-> ActivityLog
    LogCat -.-> ActivityLog
    LogDelCat -.-> ActivityLog
    
    ActivityLog -.-> AuditTrail[Audit Trail:<br/>- User ID<br/>- Action Type<br/>- Table Name<br/>- Record ID<br/>- Description<br/>- IP Address<br/>- Timestamp]
    
    %% Logout
    ViewProfile --> Logout[Logout]
    StaffMgmt --> Logout
    AssetMgmt --> Logout
    AssignMgmt --> Logout
    InspectionMgmt --> Logout
    Reports --> Logout
    UserMgmt --> Logout
    CategoryMgmt --> Logout
    SupplierMgmt --> Logout
    
    Logout --> End([Session Ended])
    
    %% Error Handling
    ErrorAvail --> AssignMgmt
    CantDelete --> CategoryMgmt
    PermDenied --> UserMgmt
    
    %% Styling
    classDef startEnd fill:#667eea,stroke:#333,stroke-width:3px,color:#fff
    classDef process fill:#4CAF50,stroke:#333,stroke-width:2px,color:#fff
    classDef decision fill:#ff9800,stroke:#333,stroke-width:2px,color:#fff
    classDef database fill:#2196F3,stroke:#333,stroke-width:2px,color:#fff
    classDef error fill:#f44336,stroke:#333,stroke-width:2px,color:#fff
    classDef success fill:#8bc34a,stroke:#333,stroke-width:2px,color:#fff
    
    class Start,End startEnd
    class Login,AddAsset,AddStaff,CreateAssign,NewInspect,AddUser,AddCat process
    class Auth,RoleCheck,CheckAvail,CalcStatus,PermCheck,CheckAssets decision
    class ActivityLog,AuditTrail database
    class ErrorAvail,CantDelete,PermDenied error
    class AssetSuccess,StaffSuccess,AssignSuccess,InspectSuccess,UserSuccess,CatSuccess success
        </div>

        <div class="info-boxes">
            <div class="info-box">
                <h3>🔐 User Roles</h3>
                <ul>
                    <li>Staff - View only access</li>
                    <li>Admin - Full management</li>
                    <li>Super Admin - System control</li>
                </ul>
            </div>
            <div class="info-box">
                <h3>📦 Core Modules</h3>
                <ul>
                    <li>Asset Management</li>
                    <li>Staff Directory</li>
                    <li>Assignment Tracking</li>
                    <li>Inspection System</li>
                    <li>Reporting Engine</li>
                </ul>
            </div>
            <div class="info-box">
                <h3>🔍 Inspection Features</h3>
                <ul>
                    <li>Security compliance checks</li>
                    <li>Physical condition assessment</li>
                    <li>14-component checklist</li>
                    <li>Quarterly scheduling</li>
                    <li>Pass/Fail status</li>
                </ul>
            </div>
            <div class="info-box">
                <h3>📊 System Features</h3>
                <ul>
                    <li>Activity logging</li>
                    <li>Audit trail</li>
                    <li>Role-based access</li>
                    <li>Multi-format reports</li>
                    <li>Dynamic forms</li>
                </ul>
            </div>
        </div>

        <a href="#" onclick="window.print(); return false;" class="download-btn">🖨️ Print Flowchart</a>
    </div>

    <script>
        mermaid.initialize({ 
            startOnLoad: true,
            theme: 'default',
            flowchart: {
                useMaxWidth: true,
                htmlLabels: true,
                curve: 'basis'
            }
        });
    </script>
</body>
</html>