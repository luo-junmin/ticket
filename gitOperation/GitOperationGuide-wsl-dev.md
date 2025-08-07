Git 多分支协作开发详细操作指南

在 Git 多分支协作开发中（主分支 `master` + 多个功能分支如 `deepin-dev`/`windows-dev`/wsl-dev/`other-dev`），需遵循标准化流程以保证代码安全性和可追溯性。以下是详细操作指南：

---

### **一、日常开发流程（分支编辑 & 推送前）**

#### 1. **切换分支前同步主分支**
```bash
git checkout master           # 切换到主分支
git pull origin master       # 拉取远程最新代码
```

#### 2. **切换到开发分支**
```bash
git checkout wsl-dev      # 进入你的开发分支
git rebase master            # 将主分支最新代码变基到当前分支
```
> 📌 **为什么用 `rebase` 而不是 `merge`？**  
> 避免产生多余的合并提交，保持提交历史线性清晰。

#### 3. **开发并提交代码**
```bash
git add .                    # 暂存更改
git commit -m "描述修改内容"  # 提交到本地分支
```

#### 4. **推送前检查冲突**
```bash
git fetch origin             # 获取远程最新状态
git diff origin/master       # 检查与主分支的差异
git rebase origin/master     # 再次变基（确保无冲突）
```

#### 5. **推送到远程分支**
```bash
git push origin wsl-dev   # 推送到同名远程分支
```
> ⚠️ 如果已推送过需强制更新（慎用）：  
> `git push --force-with-lease origin wsl-dev`

---

### **二、合并分支到主分支（Pull Request/Merge Request）**

#### 1. **本地验证分支**
```bash
git checkout master
git merge --no-ff wsl-dev  # 非快进合并（保留分支历史）
```
> 🔍 测试合并后代码是否正常运行！

#### 2. **推送到远程仓库**
```bash
git push origin master       # 直接推送（小型团队）
```
或更安全的 **GitHub/GitLab 流程：  
1. 在仓库页面发起 **Pull Request (PR)**  
2. 指定 `base: master` ← `compare: wsl-dev`  
3. 通过代码审查后点击 **Merge**

---

### **三、关键注意事项**

#### 1. **分支命名规范**
- 主分支：`master` 或 `main`  
- 功能分支：`feat/xxx`、`fix/xxx`（如 `feat/login-page`）  
- 环境分支：`wsl-dev`、`windows-dev`

#### 2. **合并冲突解决**
如果 `rebase` 或 `merge` 时冲突：
```bash
# 1. 手动编辑冲突文件
git status                  # 查看冲突文件
# 2. 解决后标记为已解决
git add 冲突文件
git rebase --continue       # 继续变基
```

#### 3. **保护主分支**
- 在仓库设置中：  
  - 启用 **Require pull request reviews**  
  - 禁止直接推送 (`Restrict direct pushes to master`)

#### 4. **分支生命周期**
```mermaid
gitGraph
    commit
    branch wsl-dev
    checkout wsl-dev
    commit
    commit
    checkout master
    merge wsl-dev
    branch windows-dev
    checkout windows-dev
    commit
```

---

### **四、完整工作流示例**
#### 场景：在 `wsl-dev` 开发新功能后合并到 `master`
```bash
# 1. 同步主分支
git checkout master
git pull origin master

# 2. 更新开发分支
git checkout wsl-dev
git rebase master

# 3. 开发完成后推送
git add .
git commit -m "添加用户登录功能"
git push origin wsl-dev

# 4. 在GitHub创建PR，审核后合并
# （或本地合并后推送）
git checkout master
git merge --no-ff wsl-dev
git push origin master
```

---

### **五、推荐工具**
- **可视化工具**：  
  - GitHub Desktop / GitKraken  
  - VS Code 的 Git 插件  
- **命令行增强**：  
  - `git log --graph --oneline --all` （查看分支拓扑图）  
  - `zsh + git插件` （自动补全分支名）

通过规范操作，既能保留各分支独立性，又能高效合并代码！