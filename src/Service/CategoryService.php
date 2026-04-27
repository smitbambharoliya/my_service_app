<?php

namespace App\Service;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

class CategoryService
{
    public function __construct(
        private EntityManagerInterface $em,
        private AdminAuditLogger $auditLogger
    ) {}

    public function createCategory(array $data): Category
    {
        $category = new Category();
        $this->updateCategory($category, $data);
        
        $this->em->persist($category);
        $this->em->flush();

        $this->auditLogger->logCategoryAction('CATEGORY_CREATE', $category->getId(), $category->getName());
        
        return $category;
    }

    public function updateCategory(Category $category, array $data): Category
    {
        $oldName = $category->getName();
        
        if (isset($data['name'])) {
            $category->setName($data['name']);
            $slugger = new AsciiSlugger();
            $category->setSlug($slugger->slug($data['name'])->lower());
        }
        
        if (isset($data['description'])) {
            $category->setDescription($data['description']);
        }
        
        if (isset($data['icon'])) {
            $category->setIcon($data['icon']);
        }
        
        if (isset($data['color'])) {
            $category->setColor($data['color']);
        }
        
        if (isset($data['sortOrder'])) {
            $category->setSortOrder((int) $data['sortOrder']);
        }

        $this->em->flush();

        if ($oldName && $oldName !== $category->getName()) {
            $this->auditLogger->logCategoryAction('CATEGORY_EDIT', $category->getId(), $category->getName(), ['old_name' => $oldName]);
        }

        return $category;
    }

    public function toggleCategory(Category $category): string
    {
        $category->setIsActive(!$category->isActive());
        $this->em->flush();

        $status = $category->isActive() ? 'activated' : 'deactivated';
        $this->auditLogger->logCategoryAction('CATEGORY_TOGGLE', $category->getId(), $category->getName(), ['status' => $status]);
        
        return $status;
    }

    public function deleteCategory(Category $category): bool
    {
        if ($category->getServiceCount() > 0) {
            return false;
        }

        $this->auditLogger->logCategoryAction('CATEGORY_DELETE', $category->getId(), $category->getName());
        $this->em->remove($category);
        $this->em->flush();

        return true;
    }
}
